<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorRootCause;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitType;
use App\Services\Visitors\VisitScoreService;
use App\Services\Visitors\VisitorReportDashboardService;
use App\Services\Visitors\VisitorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critical-deduction scoring rule:
 * Every Non-Compliant item whose snapshot severity is Critical deducts its own
 * deduction_score PLUS the configured additional penalty
 * (config('inspection.critical_deduction')) — the original
 * deduction is preserved, never replaced. The penalty applies per qualifying
 * item, everywhere a score is computed (single-visit report, reports list +
 * color filter, dashboard cards/branch ranking/inspector/trend/severity chart).
 * NA items stay excluded from scoring entirely; Critical items that are
 * Compliant (ok) deduct nothing; non-Critical items keep their existing
 * behavior. Scores are computed dynamically from the immutable
 * visitors_visit_items snapshots — nothing is stored, so nothing historical is
 * mutated.
 */
class CriticalDeductionTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private VisitorVisitType $visitType;
    private int $penalty;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->penalty = VisitScoreService::criticalDeduction();
        $this->inspector = User::factory()->create();
        $this->inspector->assignRole('Customer Support');
        $this->visitType = VisitorVisitType::where('is_active', true)->firstOrFail();
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

    /**
     * Build a completed visit whose items follow the given specs (array of
     * severity/status/deduction_score; default severity major, default status
     * nc). Every remaining item is marked Compliant. Completed directly in the
     * DB so the scoring tests isolate the calculation (the real submit route
     * additionally enforces a photo for critical NC items, which is unrelated
     * to this rule and covered elsewhere).
     */
    private function completedVisit(array $specs = []): VisitorVisit
    {
        $visit = $this->startVisit();
        $items = $visit->items()->get();

        foreach ($specs as $index => $spec) {
            $item = $items->get($index) ?: $items->first();
            $item->update(array_merge([
                'severity' => 'major',
                'status' => 'nc',
                'visited_at' => now(),
                'root_cause_id' => VisitorRootCause::firstOrFail()->id,
            ], $spec));
        }

        $visit->items()->where('status', 'pending')->update(['status' => 'ok', 'visited_at' => now()]);
        $visit->update(['status' => 'completed', 'completed_at' => now()]);

        return $visit->fresh();
    }

    // 1-3. Critical NC item = original item score + 40 (original is preserved).
    public function test_critical_nc_item_with_normal_score_5_deducts_45(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5]]);
        $this->assertDeduction($visit, 5 + $this->penalty);
    }

    public function test_critical_nc_item_with_normal_score_10_deducts_50(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 10]]);
        $this->assertDeduction($visit, 10 + $this->penalty);
    }

    public function test_critical_nc_item_with_normal_score_20_deducts_60(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20]]);
        $this->assertDeduction($visit, 20 + $this->penalty);
    }

    // 4. Each relevant status: NC deducts original + 40, OK deducts 0, NA is excluded from scoring.
    public function test_critical_item_statuses_apply_deduction_only_when_nc(): void
    {
        $visit = $this->completedVisit([
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 10],
            ['severity' => 'critical', 'status' => 'ok', 'deduction_score' => 10],
            ['severity' => 'critical', 'status' => 'na', 'deduction_score' => 10],
        ]);

        $items = $visit->items;
        $this->assertSame(10 + $this->penalty, VisitScoreService::itemDeduction($items->where('status', 'nc')->first()));
        $this->assertSame(0, VisitScoreService::itemDeduction($items->where('status', 'ok')->first()));
        $this->assertSame(0, VisitScoreService::itemDeduction($items->where('status', 'na')->first()));

        // NA is excluded from available; ok + nc remain. Deduction is 10 + penalty (nc item only).
        $expectedAvailable = $items->where('status', '!=', 'na')->sum('deduction_score');
        $this->assertSame($expectedAvailable, $visit->items->where('status', '!=', 'na')->sum('deduction_score'));
        $this->assertDeduction($visit, 10 + $this->penalty);
    }

    // 5. Multiple critical items in one visit -> per-item original + 40, not capped.
    public function test_multiple_critical_nc_items_deduct_original_plus_40_each(): void
    {
        $visit = $this->completedVisit([
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 10],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20],
        ]);

        // (5+p) + (10+p) + (20+p)
        $this->assertDeduction($visit, (5 + $this->penalty) + (10 + $this->penalty) + (20 + $this->penalty));
        $this->assertSame(3, $visit->items->where('status', 'nc')->count());
    }

    // 5b. A Critical NC item whose own score is zero still adds the configured penalty.
    public function test_critical_nc_item_with_zero_original_score_still_adds_penalty(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0]]);
        $this->assertDeduction($visit, 0 + $this->penalty);
    }

    // 6. Non-critical items keep their existing deduction_score.
    public function test_non_critical_nc_items_keep_existing_deduction(): void
    {
        $visit = $this->completedVisit([
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 6],
            ['severity' => 'minor', 'status' => 'nc', 'deduction_score' => 2],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20],
        ]);

        $expected = (20 + $this->penalty) + 6 + 2; // critical original+penalty + both non-critical unchanged
        $this->assertDeduction($visit, $expected);
    }

    // 7/8/10. Single-visit report, reports list (daily/monthly date ranges) and
    // dashboard trend all compute the same per-visit score (no double deduction).
    public function test_daily_and_monthly_reports_use_identical_critical_score(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20]]);

        $score = app(VisitScoreService::class)->calculate($visit);
        $this->assertSame(20 + $this->penalty, $score['deduction']);

        $reports = app(VisitorReportService::class);
        $dayRow = $reports->listReports(['date_from' => '2026-08-31', 'date_to' => '2026-08-31'])->first();
        $monthRow = $reports->listReports(['date_from' => '2026-08-01', 'date_to' => '2026-08-31'])->first();

        $this->assertNotNull($dayRow);
        $this->assertNotNull($monthRow);
        $this->assertSame($score['deduction'], $dayRow->score['deduction']);
        $this->assertSame($score['deduction'], $monthRow->score['deduction']);
        $this->assertSame($score['percentage'], $dayRow->score['percentage']);
        $this->assertSame($score['percentage'], $monthRow->score['percentage']);

        // The visit appears exactly once per report; its deduction is not doubled.
        $this->assertNotSame(2 * (20 + $this->penalty), $dayRow->score['deduction']);
    }

    public function test_dashboard_trend_day_and_month_groupings_match_single_visit_score(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20]]);
        $score = app(VisitScoreService::class)->calculate($visit);

        $dashboard = app(VisitorReportDashboardService::class);
        $daily = collect($dashboard->build(['grouping' => 'day'])['trend'])
            ->firstWhere('label', \Carbon\Carbon::parse('2026-08-31')->format('d M Y'));
        $monthly = collect($dashboard->build(['grouping' => 'month'])['trend'])
            ->firstWhere('label', \Carbon\Carbon::parse('2026-08-31')->format('M Y'));

        $this->assertNotNull($daily);
        $this->assertNotNull($monthly);
        $this->assertSame($score['percentage'], $daily['score']);
        $this->assertSame($score['percentage'], $monthly['score']);

        // No duplicate deduction: the percentage reflects exactly one 20+penalty deduction.
        $this->assertSame(20 + $this->penalty, $score['deduction']);
    }

    public function test_dashboard_cards_branch_ranking_and_inspector_match_single_visit(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20]]);
        $score = app(VisitScoreService::class)->calculate($visit);

        $data = app(VisitorReportDashboardService::class)->build(['grouping' => 'week']);

        $this->assertSame($score['percentage'], $data['cards']['avgScore']);
        $branch = collect($data['branchComparison'])->firstWhere('branch_id', $visit->branch_id);
        $this->assertNotNull($branch);
        $this->assertSame($score['percentage'], $branch['score']);
        $this->assertSame($score['percentage'], collect($data['inspectorPerformance'])->first()['score']);
    }

    public function test_report_severity_and_section_deductions_use_critical_override(): void
    {
        $visit = $this->completedVisit([
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20],
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 6],
        ]);

        $data = app(VisitorReportService::class)->build($visit);

        $criticalRow = $data['severity']->firstWhere('severity', 'critical');
        $majorRow = $data['severity']->firstWhere('severity', 'major');
        $this->assertSame(20 + $this->penalty, $criticalRow->deduction);
        $this->assertSame(6, $majorRow->deduction);

        $sectionRows = collect($data['sections']);
        $this->assertSame((20 + $this->penalty) + 6, $sectionRows->sum('deduction'));
    }

    public function test_dashboard_severity_chart_uses_critical_override(): void
    {
        $this->completedVisit([
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5],
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 6],
        ]);

        $rows = collect(app(VisitorReportDashboardService::class)->build([])['severity']);

        // Critical rows add the penalty to their own original scores: (20+p)+(5+p).
        $this->assertSame((20 + $this->penalty) + (5 + $this->penalty), $rows->firstWhere('name', 'critical')['deduction']);
        $this->assertSame(6, $rows->firstWhere('name', 'major')['deduction']);
    }

    // 11. Centralized configuration change without touching other files.
    public function test_changing_central_configuration_changes_the_deduction(): void
    {
        $visit = $this->completedVisit([
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20],
        ]);

        // Each item: 20 + configured penalty.
        $this->assertDeduction($visit, (20 + $this->penalty) * 2);

        config(['inspection.critical_deduction' => 30]);
        $this->assertDeduction($visit, (20 + 30) * 2); // 100

        config(['inspection.critical_deduction' => 50]);
        $this->assertDeduction($visit, (20 + 50) * 2); // 140

        // DB aggregate path reads the same config and follows the same change.
        $row = app(VisitorReportService::class)->listReports(['date_from' => '2026-08-01', 'date_to' => '2026-08-31'])->first();
        $this->assertSame(140, $row->score['deduction']);

        config(['inspection.critical_deduction' => $this->penalty]);
    }

    // 12. Scores are computed dynamically: historical completed visits re-evaluate
    // on view, and the item snapshot itself is never mutated.
    public function test_historical_visits_recompute_dynamically_without_mutating_records(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5]]);
        $item = $visit->items->where('status', 'nc')->where('severity', 'critical')->first();

        $this->assertDeduction($visit, 5 + $this->penalty);
        $this->assertSame(5, $item->fresh()->deduction_score, 'snapshot score must stay untouched');

        // Historical view now reflects the new penalty through the same dynamic path.
        config(['inspection.critical_deduction' => 55]);
        $this->assertDeduction($visit, 5 + 55);
        $this->assertSame(5, $item->fresh()->deduction_score);

        config(['inspection.critical_deduction' => $this->penalty]);
    }

    // 12/13 continuity: no score is persisted on the visits row (schema carries
    // no aggregate), so there is nothing to backfill or recalculate.
    public function test_scores_are_not_stored_on_the_visit_row(): void
    {
        $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 20]]);

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('visitors_visits');
        $this->assertNotContains('score', $columns);
        $this->assertNotContains('deduction', $columns);
        $this->assertNotContains('available', $columns);
    }

    public function test_reports_list_color_filter_matches_the_critical_scoring_rule(): void
    {
        // One critical item worth 5 against a large available pool -> below 68% (red).
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5]]);
        $score = app(VisitScoreService::class)->calculate($visit);
        $this->assertSame('red', $score['color']);

        $reports = app(VisitorReportService::class);
        $inRed = $reports->listReports(['colors' => ['red']])->pluck('id');
        $inBlue = $reports->listReports(['colors' => ['blue']])->pluck('id');

        $this->assertTrue($inRed->contains($visit->id));
        $this->assertFalse($inBlue->contains($visit->id));
    }

    public function test_report_show_page_renders_critical_deduction_score(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Admin');
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 10]]);
        $score = app(VisitScoreService::class)->calculate($visit);

        $this->actingAs($manager)
            ->get(route('visitors.reports.show', $visit))
            ->assertOk()
            ->assertSee(trim((string) $score['percentage']).'%');
    }

    public function test_violation_detail_page_shows_applied_critical_deduction(): void
    {
        $visit = $this->completedVisit([['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5]]);
        $item = $visit->items->where('status', 'nc')->firstOrFail();

        $action = \App\Models\VisitorCapaAction::create([
            'visit_id' => $visit->id,
            'visit_item_id' => $item->id,
            'title' => $item->item_title,
            'status' => 'open',
        ]);

        $this->actingAs($this->inspector)
            ->get(route('visitors.violations.show', $action))
            ->assertOk()
            ->assertSee('-'.(5 + $this->penalty));
    }

    private function assertDeduction(VisitorVisit $visit, int $expectedDeduction): void
    {
        $score = $visit->fresh()->items->isNotEmpty()
            ? app(VisitScoreService::class)->calculate($visit->fresh())
            : null;
        $this->assertNotNull($score);

        // PHP path
        $this->assertSame($expectedDeduction, $score['deduction']);

        // DB-aggregate path (reports list) must agree — no duplicated formula drift.
        $row = app(VisitorReportService::class)
            ->listReports(['date_from' => '2026-08-01', 'date_to' => '2026-08-31'])
            ->first(fn ($r) => $r->id === $visit->id);
        $this->assertNotNull($row);
        $this->assertSame($expectedDeduction, $row->score['deduction']);
        $this->assertSame($score['available'], $row->score['available']);
        $this->assertSame($score['final'], $row->score['final']);
        $this->assertSame($score['percentage'], $row->score['percentage']);

        // Report build path must agree too (daily report level).
        $built = app(VisitorReportService::class)->build($visit->fresh())['score'];
        $this->assertSame($expectedDeduction, $built['deduction']);
        $this->assertSame($score['percentage'], $built['percentage']);
    }
}