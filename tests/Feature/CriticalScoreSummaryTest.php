<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorCapaAction;
use App\Models\VisitorRootCause;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitType;
use App\Services\Visitors\VisitScoreService;
use App\Services\Visitors\VisitorReportDashboardService;
use App\Services\Visitors\VisitorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Before/after Critical-penalty score summary:
 *  - available score never changes because of the additional penalty
 *  - original deduction = sum of NC items' own deduction_score
 *  - additional Critical penalty = qualifying Critical NC item count x config
 *  - total deduction = original deduction + additional Critical penalty
 *  - before/after score, percentage and rating are exposed side by side
 *
 * Fixtures intentionally set every checklist item to a controlled score so the
 * exact before/after numbers and rating thresholds can be asserted. Only
 * visitors_visit_items participates in scoring; CAPA/follow-up rows must never
 * inflate the qualifying Critical item count.
 */
class CriticalScoreSummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private VisitorVisitType $visitType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->inspector = User::factory()->create();
        $this->inspector->assignRole('Customer Support');
        $this->visitType = VisitorVisitType::where('is_active', true)->firstOrFail();
    }

    private function branch(): Branch
    {
        return Branch::where('is_active', true)->firstOrFail();
    }

    private function startVisit(): VisitorVisit
    {
        $this->actingAs($this->inspector)->post(route('visitors.store'), [
            'visit_type_id' => $this->visitType->id,
            'branch_id' => $this->branch()->id,
            'visit_date' => '2026-09-15',
        ])->assertRedirect();

        return VisitorVisit::where('inspector_id', $this->inspector->id)->latest('id')->firstOrFail();
    }

    /**
     * Create a completed visit where every item defaults to Compliant with 0
     * points, then apply the given specs (status/severity/deduction_score) to
     * the first items. This makes available = sum of the spec scores.
     */
    private function controlledVisit(array $specs): VisitorVisit
    {
        $visit = $this->startVisit();

        $visit->items()->update([
            'status' => 'ok',
            'severity' => 'minor',
            'deduction_score' => 0,
            'visited_at' => now(),
            'root_cause_id' => VisitorRootCause::firstOrFail()->id,
        ]);

        $items = $visit->items()->get();

        foreach ($specs as $index => $spec) {
            $item = $items->get($index);
            $this->assertNotNull($item, "missing checklist item #{$index}");
            $item->update(array_merge([
                'severity' => 'major',
                'status' => 'nc',
                'visited_at' => now(),
                'root_cause_id' => VisitorRootCause::firstOrFail()->id,
            ], $spec));
        }

        $visit->update(['status' => 'completed', 'completed_at' => now()]);

        return $visit->fresh(['items']);
    }

    private function score(VisitorVisit $visit): array
    {
        return app(VisitScoreService::class)->calculate($visit->fresh(['items']));
    }

    // 1. A normal Non-Critical item with a 7-point deduction remains 7.
    public function test_non_critical_item_keeps_its_original_seven_point_deduction(): void
    {
        $visit = $this->controlledVisit([
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 7],
        ]);

        $score = $this->score($visit);

        $this->assertSame(7, $score['original_deduction']);
        $this->assertSame(0, $score['critical_penalty']);
        $this->assertSame(0, $score['critical_items']);
        $this->assertSame(7, $score['deduction']);
        $this->assertSame($score['before']['final'], $score['final']);
        $this->assertSame($score['before']['percentage'], $score['percentage']);
        $this->assertSame($score['before']['color'], $score['color']);
    }

    // 2. A Critical item with 7 original points adds the configured penalty on top.
    public function test_critical_item_adds_configured_penalty_on_top_of_original_deduction(): void
    {
        $penalty = VisitScoreService::criticalDeduction();
        $visit = $this->controlledVisit([
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 7],
        ]);

        $score = $this->score($visit);

        $this->assertSame(7, $score['original_deduction']);
        $this->assertSame(1, $score['critical_items']);
        $this->assertSame($penalty, $score['critical_penalty_per_item']);
        $this->assertSame($penalty, $score['critical_penalty']);
        $this->assertSame(7 + $penalty, $score['deduction']);
    }

    // 3. Six qualifying Critical items produce 6 x configured penalty (180 at 30).
    public function test_six_critical_items_produce_six_times_the_configured_penalty(): void
    {
        $previous = config('inspection.critical_deduction');
        config(['inspection.critical_deduction' => 30]);

        try {
            $visit = $this->controlledVisit([
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
            ]);

            $score = $this->score($visit);

            $this->assertSame(6, $score['critical_items']);
            $this->assertSame(30, $score['critical_penalty_per_item']);
            $this->assertSame(180, $score['critical_penalty']);
            $this->assertSame(180, $score['deduction']);
            $this->assertSame(0, $score['original_deduction']);
        } finally {
            config(['inspection.critical_deduction' => $previous]);
        }
    }

    // 4. Original deductions stay separate and are never replaced by the penalty.
    public function test_original_deduction_is_the_sum_of_nc_item_scores_only(): void
    {
        $penalty = VisitScoreService::criticalDeduction();
        $visit = $this->controlledVisit([
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 7],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 5],
            ['severity' => 'critical', 'status' => 'ok', 'deduction_score' => 9],
            ['severity' => 'critical', 'status' => 'na', 'deduction_score' => 11],
        ]);

        $score = $this->score($visit);

        // Only the two NC rows count; OK and NA rows never deduct.
        $this->assertSame(12, $score['original_deduction']);
        $this->assertSame(1, $score['critical_items']);
        $this->assertSame($penalty, $score['critical_penalty']);
        $this->assertSame(12 + $penalty, $score['deduction']);
    }

    // 5-10. Before/after score, percentage and rating for both stages.
    public function test_before_and_after_score_percentage_and_rating_use_existing_thresholds(): void
    {
        $previous = config('inspection.critical_deduction');
        config(['inspection.critical_deduction' => 30]);

        try {
            // available = 90 (ok) + 10 (nc major) + 0 (nc critical) = 100
            $visit = $this->controlledVisit([
                ['severity' => 'minor', 'status' => 'ok', 'deduction_score' => 90],
                ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 10],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
            ]);

            $score = $this->score($visit);

            // Available must not be reduced by the penalty.
            $this->assertSame(100, $score['available']);
            $this->assertSame(100, $score['before']['final'] + $score['before']['deduction']);

            // Before penalty: 100 - 10 = 90 -> 90.0% -> blue (>= 85).
            $this->assertSame(10, $score['before']['deduction']);
            $this->assertSame(90, $score['before']['final']);
            $this->assertSame(90.0, $score['before']['percentage']);
            $this->assertSame('blue', $score['before']['color']);

            // After penalty: 100 - (10 + 30) = 60 -> 60.0% -> red (< 68).
            $this->assertSame(10, $score['original_deduction']);
            $this->assertSame(30, $score['critical_penalty']);
            $this->assertSame(40, $score['deduction']);
            $this->assertSame(60, $score['final']);
            $this->assertSame(60.0, $score['percentage']);
            $this->assertSame('red', $score['color']);
        } finally {
            config(['inspection.critical_deduction' => $previous]);
        }
    }

    public function test_before_rating_uses_the_same_threshold_bands_as_the_final_rating(): void
    {
        $previous = config('inspection.critical_deduction');
        config(['inspection.critical_deduction' => 30]);

        try {
            // available 100, original deduction 5 -> before 95% (blue), after 65% (red).
            $visit = $this->controlledVisit([
                ['severity' => 'minor', 'status' => 'ok', 'deduction_score' => 95],
                ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 5],
                ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
            ]);

            $score = $this->score($visit);

            $this->assertSame('blue', $score['before']['color']);
            $this->assertSame('red', $score['color']);
            $this->assertSame('blue', app(VisitScoreService::class)->colorFor($score['before']['percentage']));
            $this->assertSame('red', app(VisitScoreService::class)->colorFor($score['percentage']));
        } finally {
            config(['inspection.critical_deduction' => $previous]);
        }
    }

    // 11. Daily and monthly (date-filtered) reports expose the same before/after values.
    public function test_daily_and_monthly_reports_expose_identical_before_and_after_values(): void
    {
        $visit = $this->controlledVisit([
            ['severity' => 'minor', 'status' => 'ok', 'deduction_score' => 90],
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 10],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
        ]);

        $score = $this->score($visit);
        $reports = app(VisitorReportService::class);

        $day = $reports->listReports(['date_from' => '2026-09-15', 'date_to' => '2026-09-15'])
            ->first(fn ($row) => $row->id === $visit->id);
        $month = $reports->listReports(['date_from' => '2026-09-01', 'date_to' => '2026-09-30'])
            ->first(fn ($row) => $row->id === $visit->id);

        $this->assertNotNull($day);
        $this->assertNotNull($month);

        foreach ([$day, $month] as $row) {
            $this->assertSame($score['available'], $row->score['available']);
            $this->assertSame($score['original_deduction'], $row->score['original_deduction']);
            $this->assertSame($score['critical_items'], $row->score['critical_items']);
            $this->assertSame($score['critical_penalty'], $row->score['critical_penalty']);
            $this->assertSame($score['deduction'], $row->score['deduction']);
            $this->assertSame($score['final'], $row->score['final']);
            $this->assertSame($score['percentage'], $row->score['percentage']);
            $this->assertSame($score['before']['final'], $row->score['before']['final']);
            $this->assertSame($score['before']['percentage'], $row->score['before']['percentage']);
            $this->assertSame($score['before']['color'], $row->score['before']['color']);
        }
    }

    // 12. A single qualifying item is counted/penalized once even with CAPA/follow-up rows.
    public function test_capa_and_follow_up_rows_do_not_inflate_the_critical_item_count(): void
    {
        $penalty = VisitScoreService::criticalDeduction();
        $visit = $this->controlledVisit([
            ['severity' => 'major', 'status' => 'ok', 'deduction_score' => 50],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 7],
        ]);

        $item = $visit->items->firstWhere('severity', 'critical');

        // Two CAPA rows and a follow-up for the same item must not double-count it.
        VisitorCapaAction::create([
            'visit_id' => $visit->id,
            'visit_item_id' => $item->id,
            'title' => $item->item_title,
            'status' => 'open',
        ]);
        VisitorCapaAction::create([
            'visit_id' => $visit->id,
            'visit_item_id' => $item->id,
            'title' => $item->item_title.' (duplicate action)',
            'status' => 'open',
        ]);

        $score = $this->score($visit);

        $this->assertSame(1, $score['critical_items']);
        $this->assertSame($penalty, $score['critical_penalty']);
        $this->assertSame(7 + $penalty, $score['deduction']);

        $row = app(VisitorReportService::class)
            ->listReports(['date_from' => '2026-09-01', 'date_to' => '2026-09-30'])
            ->first(fn ($r) => $r->id === $visit->id);
        $this->assertSame(1, $row->score['critical_items']);
        $this->assertSame(1, $row->getAttribute('critical_violations'));
    }

    // 13. Non-Critical scoring is untouched and before == after when no Critical item exists.
    public function test_non_critical_scoring_is_unchanged_when_no_critical_item_exists(): void
    {
        $visit = $this->controlledVisit([
            ['severity' => 'minor', 'status' => 'ok', 'deduction_score' => 80],
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 6],
            ['severity' => 'minor', 'status' => 'nc', 'deduction_score' => 2],
        ]);

        $score = $this->score($visit);

        $this->assertSame(0, $score['critical_items']);
        $this->assertSame(0, $score['critical_penalty']);
        $this->assertSame(8, $score['original_deduction']);
        $this->assertSame(8, $score['deduction']);
        $this->assertSame($score['before']['final'], $score['final']);
        $this->assertSame($score['before']['percentage'], $score['percentage']);
        $this->assertSame($score['before']['color'], $score['color']);
    }

    // Consistency: dashboard cards / branch / inspector / trend use the same rule.
    public function test_dashboard_aggregates_include_before_and_after_values_without_double_counting(): void
    {
        $penalty = VisitScoreService::criticalDeduction();
        $visit = $this->controlledVisit([
            ['severity' => 'minor', 'status' => 'ok', 'deduction_score' => 90],
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 10],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
        ]);

        $score = $this->score($visit);
        $data = app(VisitorReportDashboardService::class)->build(['grouping' => 'month']);

        $this->assertSame($score['percentage'], $data['cards']['avgScore']);
        $this->assertSame($score['before']['percentage'], $data['cards']['avgScoreBefore']);
        $this->assertSame($score['critical_items'], $data['cards']['criticalItems']);
        $this->assertSame($score['critical_penalty'], $data['cards']['criticalPenalty']);

        $branch = collect($data['branchComparison'])->firstWhere('branch_id', $visit->branch_id);
        $this->assertNotNull($branch);
        $this->assertSame($score['percentage'], $branch['score']);
        $this->assertSame($score['before']['percentage'], $branch['score_before']);
        $this->assertSame($score['critical_penalty'], $branch['critical_penalty']);

        $inspector = collect($data['inspectorPerformance'])->firstWhere('name', $this->inspector->name);
        $this->assertNotNull($inspector);
        $this->assertSame($score['before']['percentage'], $inspector['score_before']);

        $trend = collect($data['trend'])->first();
        $this->assertSame($score['percentage'], $trend['score']);
        $this->assertSame($score['before']['percentage'], $trend['score_before']);
        $this->assertSame(1, $score['critical_items'], 'one item means exactly one penalty');
        $this->assertSame($penalty, $score['critical_penalty']);
    }

    // UI: the report summary renders the three sections and the before/after values.
    public function test_report_page_renders_three_score_summary_sections(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Admin');

        $visit = $this->controlledVisit([
            ['severity' => 'minor', 'status' => 'ok', 'deduction_score' => 90],
            ['severity' => 'major', 'status' => 'nc', 'deduction_score' => 10],
            ['severity' => 'critical', 'status' => 'nc', 'deduction_score' => 0],
        ]);
        $score = $this->score($visit);

        $this->actingAs($manager)
            ->get(route('visitors.reports.show', $visit))
            ->assertOk()
            ->assertSee(__('visitors.score_before_critical_heading'))
            ->assertSee(__('visitors.additional_critical_penalty_heading'))
            ->assertSee(__('visitors.final_score_after_critical_heading'))
            ->assertSee((string) $score['before']['final'])
            ->assertSee($score['before']['percentage'].'%')
            ->assertSee($score['percentage'].'%');
    }
}
