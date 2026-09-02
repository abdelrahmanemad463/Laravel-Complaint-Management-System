<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorChecklistItem;
use App\Models\VisitorRootCause;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitType;
use App\Models\VisitorVisitItem;
use App\Services\Visitors\VisitScoreService;
use App\Services\Visitors\VisitorReportDashboardService;
use App\Services\Visitors\VisitorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $inspector;
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

        $this->visitType = VisitorVisitType::where('is_active', true)->firstOrFail();
        $this->itemCount = $this->visitType->checklistItems()->where('is_active', true)->count();
        $this->assertGreaterThan(0, $this->itemCount);

        $this->reports = app(VisitorReportService::class);
    }

    private function branch(): Branch
    {
        return Branch::where('is_active', true)->firstOrFail();
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

    private function submit(VisitorVisit $visit): void
    {
        $this->actingAs($this->inspector)->post(route('visitors.submit', $visit))
            ->assertSessionHas('success');
        $this->assertTrue($visit->fresh()->isCompleted());
    }

    private function markNc(VisitorVisit $visit, ?VisitorVisitItem $item = null): VisitorVisitItem
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

    private function makeCompletedVisit(?Branch $branch = null): VisitorVisit
    {
        $visit = $this->startVisit($branch);
        $this->markNc($visit);
        // All other items are reviewed as Compliant, matching real completed visits.
        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($visit);
        return $visit->fresh();
    }

    public function test_authorized_user_can_view_reports_list(): void
    {
        $this->actingAs($this->manager)->get(route('visitors.reports'))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_reports(): void
    {
        // Customer Support has no report.view / visit.manage.
        $this->actingAs($this->inspector)->get(route('visitors.reports'))->assertForbidden();
        $this->actingAs($this->inspector)->get(route('visitors.reports.dashboard'))->assertForbidden();
    }

    public function test_completed_visits_appear_in_reports(): void
    {
        $visit = $this->makeCompletedVisit();
        $response = $this->actingAs($this->manager)->get(route('visitors.reports'));
        $response->assertOk()->assertSee('#'.$visit->id);
    }

    public function test_in_progress_visits_do_not_appear_in_reports(): void
    {
        $visit = $this->startVisit();

        $list = $this->reports->listReports([]);
        $this->assertTrue($list->isEmpty());

        // Individual report of an in-progress visit is denied.
        $this->actingAs($this->manager)->get(route('visitors.reports.show', $visit))->assertForbidden();
    }

    public function test_score_displayed_matches_visitscore_service(): void
    {
        $visit = $this->makeCompletedVisit();
        $expected = app(VisitScoreService::class)->calculate($visit);

        $data = $this->reports->build($visit);
        $this->assertSame($expected['available'], $data['score']['available']);
        $this->assertSame($expected['deduction'], $data['score']['deduction']);
        $this->assertSame($expected['final'], $data['score']['final']);
        $this->assertSame($expected['percentage'], $data['score']['percentage']);

        $this->actingAs($this->manager)->get(route('visitors.reports.show', $visit))
            ->assertOk()
            ->assertSee(trim((string) $expected['percentage']).'%');
    }

    public function test_na_items_are_handled_correctly(): void
    {
        $visit = $this->startVisit();
        $na = $visit->items()->firstOrFail();
        $na->update(['status' => 'na', 'visited_at' => now()]);
        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($visit);

        $data = $this->reports->build($visit->fresh());
        $this->assertSame(1, $data['counts']['na']);
        // NA items are excluded from available score and never count as NC.
        $expectedAvailable = $visit->fresh()->items->where('status', '!=', 'na')->sum('deduction_score');
        $this->assertSame($expectedAvailable, $data['score']['available']);
        $nc = $visit->fresh()->items->where('status', 'nc');
        $this->assertTrue($nc->isEmpty());
    }

    public function test_severity_totals_are_correct(): void
    {
        $visit = $this->makeCompletedVisit();
        $ncItems = $visit->fresh()->items->where('status', 'nc');

        $data = $this->reports->build($visit->fresh());
        $expected = $ncItems->groupBy('severity')->map(fn ($g) => ['count' => $g->count(), 'deduction' => $g->sum('deduction_score')]);

        foreach ($data['severity'] as $row) {
            $this->assertArrayHasKey($row->severity, $expected->all());
            $this->assertSame($expected[$row->severity]['count'], $row->count);
            $this->assertSame($expected[$row->severity]['deduction'], $row->deduction);
        }
    }

    public function test_section_statistics_are_correct(): void
    {
        $visit = $this->makeCompletedVisit();
        $items = $visit->fresh()->items;

        $data = $this->reports->build($visit->fresh());
        foreach ($data['sections'] as $row) {
            $group = $items->where('section_name', $row->section);
            $applicable = $group->count() - $group->where('status', 'na')->count();
            $this->assertSame($group->count(), $row->total);
            $this->assertSame($group->where('status', 'ok')->count(), $row->ok);
            $this->assertSame($group->where('status', 'nc')->count(), $row->nc);
            $this->assertSame($group->where('status', 'na')->count(), $row->na);
            $this->assertSame($group->where('status', 'nc')->sum('deduction_score'), $row->deduction);
            if ($applicable > 0) {
                $this->assertSame(round(($row->ok / $applicable) * 100), $row->compliance);
            }
        }
    }

    public function test_root_cause_statistics_are_correct(): void
    {
        $visit = $this->makeCompletedVisit();
        $data = $this->reports->build($visit->fresh());

        foreach ($data['rootCauses'] as $row) {
            $count = $visit->fresh()->items
                ->where('status', 'nc')
                ->filter(fn ($i) => $i->rootCause?->name === $row->name)
                ->count();
            $this->assertSame($count, $row->count);
        }

        // No NC item without a root cause is counted.
        $ncWithout = $visit->fresh()->items->where('status', 'nc')->whereNull('root_cause_id');
        $this->assertTrue($ncWithout->isEmpty());
    }

    public function test_only_nc_items_appear_in_violation_details(): void
    {
        $visit = $this->makeCompletedVisit();
        $data = $this->reports->build($visit->fresh());
        $this->assertTrue($data['violations']->every(fn ($i) => $i->status === 'nc'));
    }

    public function test_capa_records_are_displayed(): void
    {
        $visit = $this->makeCompletedVisit();
        $data = $this->reports->build($visit->fresh());
        $this->assertTrue($data['capa']['actions']->count() >= 1);

        $first = $data['capa']['actions']->first();
        $this->assertNotNull($first->action);
        $this->assertFalse(empty($first->status));
    }

    public function test_historical_snapshot_values_are_used(): void
    {
        $visit = $this->startVisit();
        $itemSnapshot = $visit->items()->where('severity', '!=', 'critical')->firstOrFail();

        // Change the master checklist item after the visit started.
        VisitorChecklistItem::whereKey($itemSnapshot->checklist_item_id)->update([
            'title' => 'CHANGED MASTER TITLE',
            'severity' => 'critical',
            'deduction_score' => 99,
        ]);

        $itemSnapshot->update([
            'status' => 'nc',
            'visited_at' => now(),
            'root_cause_id' => VisitorRootCause::firstOrFail()->id,
        ]);
        $visit->items()->whereKeyNot($itemSnapshot->id)->update(['status' => 'ok', 'visited_at' => now()]);
        $this->submit($visit);

        $data = $this->reports->build($visit->fresh());

        // The report must reflect the snapshot, not the changed master value.
        $violation = $data['violations']->firstWhere('id', $itemSnapshot->id);
        $this->assertNotNull($violation);
        $this->assertSame($itemSnapshot->item_title, $violation->item_title);
        $this->assertSame($itemSnapshot->severity, $violation->severity);
        $this->assertSame($itemSnapshot->deduction_score, $violation->deduction_score);
        $this->assertSame('CHANGED MASTER TITLE', VisitorChecklistItem::find($itemSnapshot->checklist_item_id)->title);
        $this->assertNotSame('CHANGED MASTER TITLE', $violation->item_title);
    }

    public function test_pdf_generation_works(): void
    {
        $visit = $this->makeCompletedVisit();
        $response = $this->actingAs($this->manager)->get(route('visitors.reports.pdf', $visit));
        $response->assertOk();
        $contentType = $response->headers->get('content-type') ?? '';
        $this->assertStringContainsString('pdf', strtolower($contentType));
    }

    public function test_filters_return_correct_results(): void
    {
        $a = $this->branch();
        $b = Branch::where('is_active', true)->where('id', '!=', $a->id)->firstOrFail();

        $this->makeCompletedVisit($a);
        $this->makeCompletedVisit($b);

        $response = $this->actingAs($this->manager)->get(route('visitors.reports', ['branch_id' => $a->id]));
        $response->assertOk();

        $visits = $this->reports->listReports(['branch_id' => $a->id]);
        $this->assertSame(1, $visits->total());
        $this->assertSame($a->id, $visits->first()->branch_id);
    }

    public function test_branch_comparison_returns_correct_aggregation(): void
    {
        $a = $this->branch();
        $b = Branch::where('is_active', true)->where('id', '!=', $a->id)->firstOrFail();

        $this->makeCompletedVisit($a);
        $this->makeCompletedVisit($a);
        $this->makeCompletedVisit($b);

        $data = app(VisitorReportDashboardService::class)->build([]);

        $colons = collect($data['branchComparison']);
        $rowA = $colons->firstWhere('branch_id', $a->id);
        $rowB = $colons->firstWhere('branch_id', $b->id);

        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);
        $this->assertSame(2, $rowA['visits']);
        $this->assertSame(1, $rowB['visits']);
        $this->assertNotNull($rowA['score']);
        $this->assertNotNull($rowB['score']);
    }
}
