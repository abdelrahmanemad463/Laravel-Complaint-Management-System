<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorCapaAction;
use App\Models\VisitorChecklistItem;
use App\Models\VisitorRootCause;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitType;
use App\Models\VisitorViolationFollowUp;
use App\Services\Visitors\CapaService;
use App\Services\Visitors\DueDateService;
use App\Services\Visitors\VisitorReportDashboardService;
use App\Services\Visitors\VisitorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VisitorViolationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $inspector;
    private User $reviewer;
    private VisitorVisitType $visitType;
    private int $itemCount;
    private VisitorReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->manager = User::factory()->create();
        $this->manager->assignRole('Admin');

        $this->inspector = User::factory()->create();
        $this->inspector->assignRole('Customer Support');

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('Quality Manager');

        $this->visitType = VisitorVisitType::where('is_active', true)->firstOrFail();
        $this->itemCount = $this->visitType->checklistItems()->where('is_active', true)->count();
        $this->assertGreaterThan(0, $this->itemCount);

        $this->reports = app(VisitorReportService::class);
    }

    private function branch(): Branch
    {
        return Branch::where('is_active', true)->firstOrFail();
    }

    private function masterItem(?VisitorVisitType $type = null): VisitorChecklistItem
    {
        $type = $type ?: $this->visitType;
        return VisitorChecklistItem::where('visit_type_id', $type->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->firstOrFail();
    }

    private function masterNonCriticalItem(?VisitorVisitType $type = null): VisitorChecklistItem
    {
        $type = $type ?: $this->visitType;
        return VisitorChecklistItem::where('visit_type_id', $type->id)->where('is_active', true)
            ->where('severity', '!=', 'critical')->orderBy('sort_order')->orderBy('id')->firstOrFail();
    }

    private function startVisit(?Branch $branch = null): VisitorVisit
    {
        $branch = $branch ?: $this->branch();
        $this->actingAs($this->inspector)->post(route('visitors.store'), [
            'visit_type_id' => $this->visitType->id,
            'branch_id' => $branch->id,
            'visit_date' => '2026-08-31',
        ])->assertRedirect();

        return VisitorVisit::where('inspector_id', $this->inspector->id)->latest('id')->firstOrFail();
    }

    private function markNc(VisitorVisit $visit, ?\App\Models\VisitorVisitItem $item = null): \App\Models\VisitorVisitItem
    {
        $item = $item ?: $visit->items()->where('severity', '!=', 'critical')->firstOrFail();
        $item->update([
            'status' => 'nc',
            'visited_at' => now(),
            'root_cause_id' => VisitorRootCause::firstOrFail()->id,
            'note' => 'Test violation',
        ]);
        return $item->fresh();
    }

    private function submit(VisitorVisit $visit): void
    {
        $this->actingAs($this->inspector)->post(route('visitors.submit', $visit))
            ->assertSessionHas('success');
        $this->assertTrue($visit->fresh()->isCompleted());
    }

    private function makeCompletedVisit(?Branch $branch = null): VisitorVisit
    {
        $visit = $this->startVisit($branch);
        $this->markNc($visit);
        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($visit);
        return $visit->fresh();
    }

    private function actionCountOn(VisitorVisit $visit, string $masterItemId, int $branchId): int
    {
        return VisitorCapaAction::query()
            ->whereHas('visitItem', fn ($q) => $q->where('checklist_item_id', $masterItemId))
            ->whereHas('visit', fn ($q) => $q->where('branch_id', $branchId))
            ->count();
    }

    // 1. Submitting an NC item creates an open violation.
    public function test_submitting_visit_creates_open_violation(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        $this->assertSame('open', $action->status);
        $this->assertNull($action->completed_at);
        $this->assertNull($action->submitted_review_at);
    }

    // 2. A "still open" follow-up on the next inspection keeps the violation
    //    open, records a follow-up row on the new visit, and appends a timeline
    //    update — without touching the historical report.
    public function test_still_open_follow_up_keeps_violation_open_and_records_follow_up(): void
    {
        $first = $this->makeCompletedVisit();
        $action = $first->capaActions()->firstOrFail();
        $masterItemId = $action->visitItem->checklist_item_id;

        $second = $this->startVisit($first->branch);
        $matching = $second->items()->where('checklist_item_id', $masterItemId)->firstOrFail();
        $this->assertSame(0, $second->followUps()->count());

        $res = $this->actingAs($this->inspector)->postJson(route('visitors.items.follow-up', $matching), [
            'violation_ids' => [$action->id],
            'result' => 'still_open',
            'note' => 'Still non-compliant during re-inspection',
        ])->assertOk()->json();

        $this->assertSame('still_open', $res['follow_up']['result']);
        $this->assertContains($action->id, $res['follow_up']['violation_ids']);

        $fu = VisitorViolationFollowUp::firstOrFail();
        $this->assertSame($second->id, $fu->visit_id);
        $this->assertSame($matching->id, $fu->visit_item_id);
        $this->assertSame($this->inspector->id, $fu->performed_by_id);
        $this->assertSame('still_open', $fu->result);
        $this->assertSame('Still non-compliant during re-inspection', $fu->follow_up_note);
        $this->assertTrue($fu->violations->pluck('id')->contains($action->id));

        // The original open violation is untouched by a still-open follow-up.
        $this->assertSame('open', $action->fresh()->status);
        // A follow-up timeline update was appended.
        $this->assertSame(1, $action->fresh()->updates()->where('comment', 'like', '%Follow-up%')->count());
    }

    // The inspection page shows the follow-up button + candidate data (instead
    // of the legacy inline panel).
    public function test_inspection_page_shows_follow_up_button_for_existing_open_violation(): void
    {
        $first = $this->makeCompletedVisit();
        $action = $first->capaActions()->firstOrFail();
        $masterItemId = $action->visitItem->checklist_item_id;

        $second = $this->startVisit($first->branch);
        $matching = $second->items()->where('checklist_item_id', $masterItemId)->firstOrFail();

        $req = $this->actingAs($this->inspector)->get(route('visitors.show', $second));
        $req->assertOk()
            ->assertSee('data-follow-up-count="1"', false)
            ->assertSee('follow-up-open-btn', false)
            ->assertSee('data-checklist-id="'.$matching->checklist_item_id.'"', false);

        // The candidate JSON (HTML-escaped) must contain the open violation id.
        $this->assertStringContainsString('&quot;id&quot;:'.$action->id, $req->getContent());
    }

    // 3. New violation allowed after the previous one is closed; the follow-up
    //    button is no longer offered for a closed violation.
    public function test_new_violation_allowed_after_previous_is_closed(): void
    {
        $first = $this->makeCompletedVisit();
        $action = $first->capaActions()->firstOrFail();
        $masterItemId = $action->visitItem->checklist_item_id;
        $branchId = $first->branch_id;

        // Close the previous violation (resolve → reviewer approve).
        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'Fixed',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ])->assertSessionHas('success');
        $this->actingAs($this->reviewer)->post(route('visitors.violations.approve', $action))->assertRedirect();
        $this->assertSame('closed', $action->fresh()->status);

        $second = $this->startVisit($first->branch);
        $matching = $second->items()->where('checklist_item_id', $masterItemId)->firstOrFail();
        $this->actingAs($this->inspector)->putJson(route('visitors.items.update', $matching), [
            'status' => 'nc',
            'root_cause_id' => VisitorRootCause::firstOrFail()->id,
            'note' => 'New occurrence',
        ])->assertOk();
        $second->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($second);

        $this->assertSame(2, $this->actionCountOn($second->fresh(), $masterItemId, $branchId));

        // Closed violations are not eligible for the follow-up modal (count = 0).
        $this->actingAs($this->inspector)->get(route('visitors.show', $second))
            ->assertOk()
            ->assertDontSee('data-follow-up-count="1"', false);
    }

    // 4. Submitting resolution moves a violation to pending review.
    public function test_resolve_moves_violation_to_pending_review(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        Storage::fake('local');
        $photo = UploadedFile::fake()->image('resolution.jpg', 200, 200);

        $this->actingAs($this->inspector)
            ->post(route('visitors.violations.resolve', $action), [
                'note' => 'Fixed on site',
                'photo' => $photo,
            ])->assertSessionHas('success', __('visitors.violation_submitted_for_review'));

        $action->refresh();
        $this->assertSame('pending_review', $action->status);
        $this->assertNotNull($action->submitted_review_at);
        // Resolution photo attached to the origin visit item.
        $item = $visit->items()->findOrFail($action->visit_item_id);
        $this->assertSame(1, $item->photos()->where('capa_action_id', $action->id)->where('evidence_role', 'resolution')->count());
    }

    // 5. Inspector cannot approve/reject (needs visit.review).
    public function test_inspector_cannot_approve_or_reject(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();
        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);

        $this->actingAs($this->inspector)->post(route('visitors.violations.approve', $action))->assertForbidden();
        $this->actingAs($this->inspector)->post(route('visitors.violations.reject', $action), ['reject_reason' => 'No'])->assertForbidden();
        $this->assertSame('pending_review', $action->fresh()->status);
    }

    // 6. Quality Manager can approve → closed.
    public function test_reviewer_approves_violation_to_closed(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);

        $this->actingAs($this->reviewer)->post(route('visitors.violations.approve', $action), ['comment' => 'Good work'])
            ->assertSessionHas('success', __('visitors.violation_approved'));

        $action->refresh();
        $this->assertSame('closed', $action->status);
        $this->assertNotNull($action->completed_at);
        $this->assertSame($this->reviewer->id, $action->reviewed_by);
    }

    // 7. Reviewer rejects → back to in_progress with reject_reason.
    public function test_reviewer_reject_returns_to_in_progress_with_reason(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);

        $this->actingAs($this->reviewer)->post(route('visitors.violations.reject', $action), ['reject_reason' => 'Evidence unclear'])
            ->assertSessionHas('success', __('visitors.violation_rejected'));

        $action->refresh();
        $this->assertSame('in_progress', $action->status);
        $this->assertSame('Evidence unclear', $action->reject_reason);
        $this->assertNull($action->completed_at);
    }

    // 8. Missing initial photo for critical NC blocks submission (no violation created).
    public function test_critical_initial_evidence_required_on_submit(): void
    {
        $visit = $this->startVisit();
        $critical = $visit->items()->where('severity', 'critical')->first();
        $this->assertNotNull($critical, 'Seed must include a critical item for this test.');
        $critical->update(['status' => 'nc', 'visited_at' => now(), 'root_cause_id' => VisitorRootCause::firstOrFail()->id]);
        $visit->items()->whereKeyNot($critical->id)->update(['status' => 'ok', 'visited_at' => now()]);

        $this->actingAs($this->inspector)->post(route('visitors.submit', $visit))
            ->assertSessionHas('error', __('visitors.evidence_photo_required'));

        $this->assertSame('in_progress', $visit->fresh()->status);
        $this->assertCount(0, $visit->fresh()->capaActions);
    }

    // 9. Critical violations require resolution evidence before pending review.
    public function test_critical_resolution_evidence_required(): void
    {
        $visit = $this->startVisit();
        $critical = $visit->items()->where('severity', 'critical')->first();
        $this->assertNotNull($critical, 'Seed must include a critical item for this test.');
        $critical->update(['status' => 'nc', 'visited_at' => now(), 'root_cause_id' => VisitorRootCause::firstOrFail()->id]);
        $visit->items()->whereKeyNot($critical->id)->update(['status' => 'ok', 'visited_at' => now()]);

        // Critical evidence for the initial inspection is required first.
        $initial = UploadedFile::fake()->image('initial.jpg', 200, 200);
        $this->actingAs($this->inspector)->post(route('visitors.items.photo', $critical), ['photo' => $initial])->assertOk();
        $this->submit($visit);

        $action = $visit->fresh()->capaActions()->firstOrFail();
        $this->assertSame('critical', $action->visitItem->severity);

        // Service-level: no resolution evidence → cannot go to pending review.
        $this->expectException(RuntimeException::class);
        app(CapaService::class)->submitResolution($action, 'Fixed');
    }

    // 9b. The resolve endpoint rejects a missing photo outright (422).
    public function test_resolve_endpoint_requires_photo(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), ['note' => 'no photo'])
            ->assertSessionHasErrors('photo');
        $this->assertSame('open', $action->fresh()->status);
    }

    // 9c. Critical violations can be resolved via the endpoint WITH resolution evidence.
    public function test_critical_resolution_with_evidence_succeeds(): void
    {
        $visit = $this->startVisit();
        $critical = $visit->items()->where('severity', 'critical')->first();
        $this->assertNotNull($critical, 'Seed must include a critical item for this test.');
        $critical->update(['status' => 'nc', 'visited_at' => now(), 'root_cause_id' => VisitorRootCause::firstOrFail()->id]);
        $visit->items()->whereKeyNot($critical->id)->update(['status' => 'ok', 'visited_at' => now()]);

        $initial = UploadedFile::fake()->image('initial.jpg', 200, 200);
        $this->actingAs($this->inspector)->post(route('visitors.items.photo', $critical), ['photo' => $initial])->assertOk();
        $this->submit($visit);

        $action = $visit->fresh()->capaActions()->firstOrFail();

        Storage::fake('local');
        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'Fixed on site',
            'photo' => UploadedFile::fake()->image('resolution.jpg', 200, 200),
        ])->assertSessionHas('success');

        $this->assertSame('pending_review', $action->fresh()->status);
    }

    // 10. Historical report is immutable: score/violations unchanged by follow-ups.
    public function test_historical_report_unchanged_by_follow_up(): void
    {
        $visit = $this->makeCompletedVisit();
        $before = $this->reports->build($visit);
        $action = $visit->capaActions()->firstOrFail();

        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);
        $this->actingAs($this->reviewer)->post(route('visitors.violations.approve', $action));

        $after = $this->reports->build($visit->fresh());
        $this->assertSame($before['score'], $after['score']);
        $this->assertSame($before['violations']->count(), $after['violations']->count());
        $this->assertSame(
            collect($before['violations'])->pluck('item.id')->all(),
            collect($after['violations'])->pluck('item.id')->all()
        );
        // CAPA snapshot fields (period_hours, due_at) untouched by the lifecycle.
        $a = $action->fresh();
        $this->assertNotNull($a->due_at);
        $this->assertSame(48.0, $a->period_hours);
    }

    // 11. Due date is never reset by follow-up activity.
    public function test_due_date_is_not_reset_on_follow_up(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();
        $dueAt = $action->due_at;

        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);
        $this->actingAs($this->reviewer)->post(route('visitors.violations.approve', $action));

        $fresh = $action->fresh();
        $this->assertTrue($dueAt->equalTo($fresh->due_at));
        $this->assertSame(48.0, $fresh->period_hours);
    }

    // 12. Immediate checklists (period_hours = 0) get no due date.
    public function test_immediate_action_has_null_due_date(): void
    {
        $master = $this->masterNonCriticalItem();
        $master->update(['period_hours' => 0]);

        $visit = $this->startVisit();
        $item = $visit->items()->where('checklist_item_id', $master->id)->firstOrFail();
        $this->markNc($visit, $item);
        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($visit);

        $action = $visit->fresh()->capaActions()->firstOrFail();
        $this->assertSame(0.0, $action->period_hours);
        $this->assertNull($action->due_at);
        $this->assertSame('immediate', $action->dueStatus());
    }

    // 13. CAPA snapshot survives master-data changes after the visit started.
    public function test_capa_snapshot_survives_master_data_change(): void
    {
        $master = $this->masterNonCriticalItem();
        $snapshotPeriod = $master->period_hours;

        $visit = $this->startVisit();
        $item = $visit->items()->where('checklist_item_id', $master->id)->firstOrFail();

        // Change the master checklist item after the visit started.
        $master->update(['title' => 'CHANGED TITLE', 'period_hours' => ($snapshotPeriod ?? 48) + 120, 'severity' => 'critical', 'deduction_score' => 99]);

        $this->markNc($visit, $item);
        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($visit);

        $action = $visit->fresh()->capaActions()->firstOrFail();
        $this->assertSame($item->period_hours, $action->period_hours);
        $this->assertNotSame($master->fresh()->period_hours, $action->period_hours);
        $this->assertSame($item->item_title, $action->visitItem->item_title);
        $this->assertNotSame('CHANGED TITLE', $action->visitItem->item_title);
    }

    // Dashboard: pending-review violations appear on the pending-review card.
    public function test_dashboard_counts_pending_review_violations(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        $cards = app(VisitorReportDashboardService::class)->build([])['cards'];
        $this->assertSame(0, $cards['pendingReviewCapa']);

        $this->actingAs($this->inspector)->post(route('visitors.violations.resolve', $action), [
            'note' => 'resolved',
            'photo' => UploadedFile::fake()->image('r.jpg'),
        ]);

        $cards = app(VisitorReportDashboardService::class)->build([])['cards'];
        $this->assertSame(1, $cards['pendingReviewCapa']);
        $this->assertSame(0, $cards['overdueCapa']);

        // Dashboard page is viewable by the reviewer.
        $this->actingAs($this->reviewer)->get(route('visitors.reports.dashboard'))->assertOk()->assertSee(__('visitors.pending_review_capa'));
    }

    // Violations index and show pages are gated behind visit.view.
    public function test_violations_pages_accessible_and_filtered(): void
    {
        $visit = $this->makeCompletedVisit();
        $action = $visit->capaActions()->firstOrFail();

        $this->actingAs($this->manager)->get(route('visitors.violations'))->assertOk()->assertSee('#V-'.$action->id);
        $this->actingAs($this->manager)->get(route('visitors.violations.show', $action))->assertOk();

        // Inspector can view too (visit.view) but not review.
        $this->actingAs($this->inspector)->get(route('visitors.violations.show', $action))->assertOk();
    }
}