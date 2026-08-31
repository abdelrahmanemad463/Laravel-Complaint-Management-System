<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorRootCause;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use App\Models\VisitorVisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitorsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private VisitorVisitType $visitType;
    private int $itemCount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $this->user->assignRole('Customer Support');
        $this->visitType = VisitorVisitType::where('is_active', true)->firstOrFail();
        $this->itemCount = $this->visitType->checklistItems()->where('is_active', true)->count();
        $this->assertGreaterThan(0, $this->itemCount);
    }

    private function startVisit(?int $branchId = null): VisitorVisit
    {
        $branch = $branchId ? Branch::findOrFail($branchId) : Branch::where('is_active', true)->firstOrFail();
        $this->actingAs($this->user)->post(route('visitors.store'), [
            'visit_type_id' => $this->visitType->id,
            'branch_id' => $branch->id,
            'visit_date' => '2026-08-31',
        ])->assertRedirect();
        return VisitorVisit::where('inspector_id', $this->user->id)->firstOrFail();
    }

    public function test_create_stores_authenticated_inspector_and_in_progress_items_default_ok(): void
    {
        $visit = $this->startVisit();
        $this->assertSame('in_progress', $visit->status);
        $this->assertSame($this->user->id, $visit->inspector_id);
        $this->assertSame($this->itemCount, $visit->items()->count());
        $this->assertSame($this->itemCount, $visit->items()->where('status', 'ok')->count());
        $this->assertSame($this->itemCount, $visit->items()->whereNotNull('visited_at')->count());
    }

    public function test_open_visits_only_shows_own_in_progress_visits(): void
    {
        $mine = $this->startVisit();
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Customer Support');
        $branch = Branch::where('is_active', true)->orderBy('id')->skip(1)->firstOrFail();
        $this->actingAs($otherUser)->post(route('visitors.store'), [
            'visit_type_id' => $this->visitType->id,
            'branch_id' => $branch->id,
            'visit_date' => '2026-08-31',
        ])->assertRedirect();

        $response = $this->actingAs($this->user)->get(route('visitors.open'));
        $response->assertOk()->assertSee($mine->branch->name)->assertDontSee($branch->name);
    }

    public function test_foreign_visit_returns_403(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Customer Support');
        $branch = Branch::where('is_active', true)->orderBy('id')->skip(1)->firstOrFail();
        $this->actingAs($otherUser)->post(route('visitors.store'), [
            'visit_type_id' => $this->visitType->id,
            'branch_id' => $branch->id,
            'visit_date' => '2026-08-31',
        ]);

        $foreign = VisitorVisit::where('inspector_id', $otherUser->id)->firstOrFail();
        $this->actingAs($this->user)->get(route('visitors.show', $foreign))->assertForbidden();
    }

    public function test_autosave_persists_answer_and_sets_visited_at(): void
    {
        $visit = $this->startVisit();
        $item = $visit->items()->first();
        $rc = VisitorRootCause::firstOrFail();

        $this->actingAs($this->user)
            ->putJson(route('visitors.items.update', $item), [
                'status' => 'nc',
                'root_cause_id' => $rc->id,
                'note' => 'Found gap',
            ])->assertOk()->assertJson(['ok' => true, 'status' => 'nc']);

        $item->refresh();
        $this->assertSame('nc', $item->status);
        $this->assertSame($rc->id, $item->root_cause_id);
        $this->assertSame('Found gap', $item->note);
        $this->assertNotNull($item->visited_at);
    }

    public function test_resume_renders_inspection_page(): void
    {
        $visit = $this->startVisit();
        $this->actingAs($this->user)->get(route('visitors.show', $visit))
            ->assertOk()->assertSee($visit->branch->name)->assertSee($this->visitType->name);
    }

    public function test_progress_based_on_visited_at(): void
    {
        $visit = $this->startVisit();
        $item = $visit->items()->first();
        $this->actingAs($this->user)->putJson(route('visitors.items.update', $item), ['status' => 'ok']);
        $item->refresh();
        $this->assertNotNull($item->visited_at);

        $response = $this->actingAs($this->user)->get(route('visitors.show', $visit));
        $response->assertOk()->assertSee($this->itemCount.' / '.$this->itemCount);
    }

    public function test_na_items_excluded_from_score(): void
    {
        $this->assertGreaterThanOrEqual(2, $this->itemCount);
        $visit = $this->startVisit();
        $naItem = $visit->items()->first();
        $this->actingAs($this->user)->putJson(route('visitors.items.update', $naItem), ['status' => 'na']);

        $scoreService = app(\App\Services\Visitors\VisitScoreService::class);
        $visit->load('items');
        $score = $scoreService->calculate($visit);
        $this->assertSame($visit->items()->sum('deduction_score') - $naItem->deduction_score, $score['available']);
    }

    public function test_score_correctness_excluding_na_and_deducting_nc(): void
    {
        $visit = $this->startVisit();
        $items = $visit->items()->get();
        $nc = $items->first();
        $na = $items->last();
        $this->actingAs($this->user)->putJson(route('visitors.items.update', $nc), ['status' => 'nc']);
        $this->actingAs($this->user)->putJson(route('visitors.items.update', $na), ['status' => 'na']);

        $visit->load('items');
        $score = app(\App\Services\Visitors\VisitScoreService::class)->calculate($visit);
        $expectedAvailable = $visit->items->sum('deduction_score') - $na->deduction_score;
        $expectedFinal = $expectedAvailable - $nc->deduction_score;
        $this->assertSame($expectedAvailable, $score['available']);
        $this->assertSame($expectedFinal, $score['final']);
        $this->assertSame(round($expectedFinal / $expectedAvailable * 100, 1), $score['percentage']);
    }

    public function test_fresh_visit_submits_with_default_ok_reviewed_items(): void
    {
        $visit = $this->startVisit();
        $this->actingAs($this->user)->post(route('visitors.submit', $visit))
            ->assertSessionHas('success');
        $this->assertSame('completed', $visit->fresh()->status);
    }

    public function test_reviewed_nc_critical_without_photo_blocks_submission(): void
    {
        $visit = $this->startVisit();
        // Simulate an uncertain critical item that was marked NC without a photo.
        $critical = $visit->items()->where('severity', 'critical')->first()
            ?? $visit->items()->orderBy('deduction_score', 'desc')->first();
        $critical->update(['status' => 'nc', 'root_cause_id' => VisitorRootCause::firstOrFail()->id]);
        // All other items stay default ok + reviewed.
        $this->actingAs($this->user)->post(route('visitors.submit', $visit))
            ->assertSessionHas('error', __('visitors.evidence_photo_required'));
        $this->assertSame('in_progress', $visit->fresh()->status);
    }

    public function test_submission_creates_capa_and_completes_visit(): void
    {
        $visit = $this->startVisit();
        $nc = $visit->items()->where('severity', '!=', 'critical')->first();
        $this->assertNotNull($nc);
        $nc->update(['status' => 'nc', 'visited_at' => now(), 'root_cause_id' => VisitorRootCause::firstOrFail()->id]);
        $visit->items()->whereKeyNot($nc->id)->update(['visited_at' => now()]);

        $this->actingAs($this->user)->post(route('visitors.submit', $visit))
            ->assertSessionHas('success');

        $visit->refresh();
        $this->assertSame('completed', $visit->status);
        $this->assertNotNull($visit->completed_at);
        $this->assertDatabaseHas('visitors_capa_actions', ['visit_id' => $visit->id, 'visit_item_id' => $nc->id]);
        $this->assertDatabaseHas('visitors_capa_updates', ['capa_action_id' => $visit->capaActions()->first()->id]);
    }

    public function test_completed_visit_immutable(): void
    {
        $visit = $this->startVisit();
        $visit->items()->update(['visited_at' => now()]);
        $this->actingAs($this->user)->post(route('visitors.submit', $visit));
        $item = $visit->items()->first();

        $this->actingAs($this->user)
            ->putJson(route('visitors.items.update', $item), ['status' => 'na'])
            ->assertForbidden();
    }

    public function test_critical_nc_item_requires_photo_on_submit(): void
    {
        $visit = $this->startVisit();
        $critical = $visit->items()->where('severity', 'critical')->first()
            ?? $visit->items()->orderBy('deduction_score', 'desc')->first();
        $critical->update(['status' => 'nc', 'visited_at' => now(), 'root_cause_id' => VisitorRootCause::firstOrFail()->id]);
        $visit->items()->whereKeyNot($critical->id)->update(['visited_at' => now()]);

        $this->actingAs($this->user)->post(route('visitors.submit', $visit))
            ->assertSessionHas('error', __('visitors.evidence_photo_required'));
        $this->assertSame('in_progress', $visit->fresh()->status);
    }

    public function test_oversized_photo_rejected(): void
    {
        $visit = $this->startVisit();
        $item = $visit->items()->first();
        Storage::fake('local');
        $file = UploadedFile::fake()->image('big.jpg')->size(21 * 1024 * 1024);

        $this->actingAs($this->user)
            ->postJson(route('visitors.items.photo', $item), ['photo' => $file])
            ->assertStatus(422);
    }

    public function test_snapshot_stable_after_submission(): void
    {
        $visit = $this->startVisit();
        $item = $visit->items()->first();
        $title = $item->item_title;
        $visit->items()->update(['visited_at' => now()]);
        $this->actingAs($this->user)->post(route('visitors.submit', $visit));

        $this->assertSame($title, $item->fresh()->item_title);
    }
}
