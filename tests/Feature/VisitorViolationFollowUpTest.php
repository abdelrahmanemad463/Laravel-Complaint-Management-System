<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorChecklistItem;
use App\Models\VisitorRootCause;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use App\Models\VisitorVisitType;
use App\Models\VisitorViolationFollowUp;
use App\Services\Visitors\VisitorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitorViolationFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private User $reviewer;
    private User $manager;
    private VisitorVisitType $visitType;
    private VisitorReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->inspector = User::factory()->create();
        $this->inspector->assignRole('Customer Support');

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('Quality Manager');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('Admin');

        $this->visitType = VisitorVisitType::where('is_active', true)->firstOrFail();
        $this->reports = app(VisitorReportService::class);
    }

    private function branch(): Branch
    {
        return Branch::where('is_active', true)->firstOrFail();
    }

    private function nonCriticalMaster(): VisitorChecklistItem
    {
        return VisitorChecklistItem::where('visit_type_id', $this->visitType->id)
            ->where('is_active', true)
            ->where('severity', '!=', 'critical')
            ->orderBy('sort_order')->orderBy('id')->firstOrFail();
    }

    private function itemOn(VisitorVisit $visit, ?VisitorChecklistItem $master = null): VisitorVisitItem
    {
        $master = $master ?: $this->nonCriticalMaster();
        return $visit->items()->where('checklist_item_id', $master->id)->firstOrFail();
    }

    private function startVisit(?Branch $branch = null): VisitorVisit
    {
        $branch = $branch ?: $this->branch();
        $this->actingAs($this->inspector)->post(route('visitors.store'), [
            'visit_type_id' => $this->visitType->id,
            'branch_id' => $branch->id,
            'visit_date' => '2026-09-01',
        ])->assertRedirect();

        return VisitorVisit::where('inspector_id', $this->inspector->id)->latest('id')->firstOrFail();
    }

    private function markNc(VisitorVisitItem $item): VisitorVisitItem
    {
        $item->update([
            'status' => 'nc',
            'visited_at' => now(),
            'root_cause_id' => VisitorRootCause::firstOrFail()->id,
            'note' => 'Test violation',
        ]);
        return $item->fresh();
    }

    private function completeRest(VisitorVisit $visit): void
    {
        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
    }

    private function submit(VisitorVisit $visit): void
    {
        $this->actingAs($this->inspector)->post(route('visitors.submit', $visit))
            ->assertSessionHas('success');
        $this->assertTrue($visit->fresh()->isCompleted());
    }

    /**
     * A completed visit with one NC on the given master item → one open action.
     */
    private function makeViolatedVisit(?VisitorChecklistItem $master = null, ?Branch $branch = null): array
    {
        $visit = $this->startVisit($branch);
        $item = $this->itemOn($visit, $master);
        $this->markNc($item);
        $this->completeRest($visit);
        $this->submit($visit);
        $visit = $visit->fresh();
        return [$visit, $visit->capaActions()->firstOrFail(), $visit->items()->where('checklist_item_id', $item->checklist_item_id)->firstOrFail()];
    }

    // Eligible: an open violation of the same item + branch shows the button.
    public function test_open_violation_candidate_shows_follow_up_button(): void
    {
        [$first] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->get(route('visitors.show', $second))
            ->assertOk()
            ->assertSee('data-follow-up-count="1"', false)
            ->assertSee('follow-up-open-btn', false);
    }

    // No candidates → the follow-up button is absent.
    public function test_no_candidates_hide_follow_up_button(): void
    {
        $branch = $this->branch();
        $this->startVisit($branch); // first visit with no violations

        $second = $this->startVisit($branch);
        $this->actingAs($this->inspector)->get(route('visitors.show', $second))
            ->assertOk()
            ->assertDontSee('data-follow-up-count="1"', false);
    }

    // Multiple open violations on the same item → count shows N, all selectable.
    public function test_multiple_violations_show_cumulative_count_and_multi_select(): void
    {
        $master = $this->nonCriticalMaster();
        $branch = $this->branch();
        $this->makeViolatedVisit($master, $branch); // violation 1
        $this->makeViolatedVisit($master, $branch); // violation 2

        $third = $this->startVisit($branch);
        $matching = $this->itemOn($third, $master);

        $req = $this->actingAs($this->inspector)->get(route('visitors.show', $third));
        $req->assertOk()
            ->assertSee('data-follow-up-count="2"', false)
            ->assertSee('follow-up-open-btn', false);

        $html = $req->getContent();
        $this->assertStringContainsString('&quot;id&quot;:', $html);

        // Selecting both violations creates a single follow-up record.
        $openActions = \App\Models\VisitorCapaAction::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('visitItem', fn ($q) => $q->where('checklist_item_id', $master->id))
            ->whereHas('visit', fn ($q) => $q->where('branch_id', $branch->id)->where('status', 'completed'))
            ->orderBy('id')
            ->get();

        $res = $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => $openActions->pluck('id')->all(),
            'result' => 'still_open',
            'note' => 'Both still open',
        ])->assertOk()->json();

        $fu = VisitorViolationFollowUp::firstOrFail();
        $this->assertSame($third->id, $fu->visit_id);
        $this->assertSame($matching->id, $fu->visit_item_id);
        $this->assertSame('still_open', $fu->result);
        $this->assertSame($openActions->pluck('id')->sort()->values()->all(), $fu->violations->pluck('id')->sort()->values()->all());
        $this->assertCount(1, $third->followUps()->get());
        $this->assertSame($openActions->pluck('id')->sort()->values()->all(), collect($res['follow_up']['violation_ids'])->sort()->values()->all());
    }

    // Note-only follow-up (no photos).
    public function test_follow_up_with_note_only(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
            'note' => 'Still not fixed',
        ])->assertOk();

        $fu = VisitorViolationFollowUp::firstOrFail();
        $this->assertSame('Still not fixed', $fu->follow_up_note);
        $this->assertSame(0, $fu->photos->count());
    }

    // Follow-up with neither note nor photos.
    public function test_follow_up_without_note_or_photos(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertOk();

        $fu = VisitorViolationFollowUp::firstOrFail();
        $this->assertNull($fu->follow_up_note);
        $this->assertSame(0, $fu->photos->count());
    }

    // Follow-up evidence photos are stored with the follow_up role.
    public function test_follow_up_photos_stored_with_role(): void
    {
        Storage::fake('local');

        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->post(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
            'photos' => [
                UploadedFile::fake()->image('fu1.jpg', 200, 200),
                UploadedFile::fake()->image('fu2.png', 200, 200),
            ],
        ])->assertOk();

        $fu = VisitorViolationFollowUp::firstOrFail();
        $this->assertSame(2, $fu->photos->count());
        foreach ($fu->photos as $photo) {
            $this->assertSame('follow_up', $photo->evidence_role);
            $this->assertSame($fu->id, $photo->violation_follow_up_id);
            $this->assertSame($second->id, $photo->visit_id);
            $this->assertSame($matching->id, $photo->visit_item_id);
        }
        // Photos live on the item as follow-up evidence.
        $this->assertSame(2, $matching->photos()->where('evidence_role', 'follow_up')->count());
    }

    // Resolved follow-up moves each referenced violation to pending review.
    public function test_resolved_follow_up_moves_previous_violation_to_pending_review(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'resolved',
            'note' => 'Fixed during re-inspection',
        ])->assertOk();

        $action->refresh();
        $this->assertSame('pending_review', $action->status);
        $this->assertNotNull($action->submitted_review_at);
        $this->assertSame(
            1,
            $action->fresh()->updates()->where('comment', 'like', '%Resolved via follow-up%')->count()
        );

        // Reviewer can still approve → closed.
        $this->actingAs($this->reviewer)->post(route('visitors.violations.approve', $action))->assertRedirect();
        $this->assertSame('closed', $action->fresh()->status);
    }

    // Still-open keeps the violation eligible for later follow-ups.
    public function test_still_open_keeps_violation_listed_for_later(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertOk();

        // Still open → still eligible on the same visit page thereafter.
        $this->actingAs($this->inspector)->get(route('visitors.show', $second))
            ->assertSee('data-follow-up-count="1"', false);
    }

    // A resolved (pending_review) violation is no longer eligible.
    public function test_pending_review_violation_not_eligible(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);
        $this->assertSame('pending_review', $action->fresh()->status);

        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->get(route('visitors.show', $second))
            ->assertDontSee('data-follow-up-count="1"', false);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertStatus(422);
    }

    // A violation belonging to a different checklist item is rejected.
    public function test_follow_up_rejects_different_item(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $other = $second->items()
            ->where('checklist_item_id', '!=', $action->visitItem->checklist_item_id)
            ->firstOrFail();

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $other), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertStatus(422)->assertJsonFragment(['message' => __('visitors.follow_up_invalid_violation')]);

        $this->assertSame(0, $second->followUps()->count());
    }

    // A violation from another branch is rejected.
    public function test_follow_up_rejects_different_branch(): void
    {
        $branchA = $this->branch();
        $branchB = Branch::where('is_active', true)->where('id', '!=', $branchA->id)->firstOrFail();

        [$first, $action] = $this->makeViolatedVisit(null, $branchA);
        $second = $this->startVisit($branchB);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertStatus(422);
    }

    // A closed violation fails the eligibility check (422).
    public function test_follow_up_rejects_closed_violation(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'fixed',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);
        $this->actingAs($this->reviewer)->post(route('visitors.violations.approve', $action));
        $this->assertSame('closed', $action->fresh()->status);

        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertStatus(422)->assertJsonFragment(['message' => __('visitors.follow_up_invalid_violation')]);
    }

    // Follow-up requires the visit.update permission / being the inspector.
    public function test_follow_up_requires_permission(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $outsider = User::factory()->create(); // no role / permission
        $this->actingAs($outsider)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertForbidden();
    }

    // Completed visits cannot get follow-ups (owner is blocked by policy).
    public function test_follow_up_rejected_on_completed_visit(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        [$second, , $matching] = $this->makeViolatedVisit(); // second is completed
        $this->assertTrue($second->isCompleted());

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
        ])->assertForbidden();
    }

    // Validation: violation_ids and result are required.
    public function test_follow_up_validation(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['violation_ids', 'result']);

        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'bogus',
        ])->assertStatus(422)->assertJsonValidationErrors('result');
    }

    // The previous visit's historical report is untouched by a follow-up.
    public function test_historical_report_unchanged_by_follow_up(): void
    {
        [$first, $action] = $this->makeViolatedVisit();
        $before = $this->reports->build($first);

        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);
        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'resolved',
            'note' => 'Fixed now',
        ])->assertOk();

        $after = $this->reports->build($first->fresh());
        $this->assertSame($before['score'], $after['score']);
        $this->assertSame($before['violations']->count(), $after['violations']->count());
        // Follow-ups are not part of the origin visit's report.
        $this->assertSame(0, $before['followUps']->count());
        $this->assertSame(0, $after['followUps']->count());
    }

    // The follow-up appears in the NEW visit's report.
    public function test_new_visit_report_lists_follow_ups(): void
    {
        [$first, $action] = $this->makeViolatedVisit();

        $second = $this->startVisit($first->branch);
        $matching = $this->itemOn($second);
        $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'resolved',
            'note' => 'Fixed now',
        ])->assertOk();

        $this->completeRest($second);
        $this->submit($second);

        $report = $this->reports->build($second->fresh());
        $this->assertSame(1, $report['followUps']->count());

        $entry = $report['followUps']->first();
        $this->assertSame('resolved', $entry->followUp->result);
        $this->assertSame('Fixed now', $entry->followUp->follow_up_note);
        $this->assertSame($this->inspector->id, $entry->followUp->performed_by_id);
        $this->assertTrue($entry->followUp->violations->pluck('id')->contains($action->id));
        $this->assertSame($matching->id, $entry->item->id);

        // The report page renders the follow-up section.
        $this->actingAs($this->manager)->get(route('visitors.reports.show', $second))
            ->assertOk()
            ->assertSee(__('visitors.follow_up_section_title'))
            ->assertSee('#V-'.$action->id);
    }
}