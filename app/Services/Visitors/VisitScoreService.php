<?php

namespace App\Services\Visitors;

use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use Illuminate\Support\Collection;

class VisitScoreService
{
    /**
     * The configured penalty added ON TOP of a qualifying Critical item's own
     * deduction (the original deduction is preserved). Lives in one documented
     * place: config('inspection.critical_deduction').
     */
    public static function criticalDeduction(): int
    {
        return (int) config('inspection.critical_deduction', 40);
    }

    /**
     * Per-item deduction used by every scoring path. A Non-Compliant item
     * always deducts its own configured deduction_score; when its severity
     * snapshot is Critical an ADDITIONAL fixed penalty (config
     * 'inspection.critical_deduction', default 40) is added on top — the
     * original deduction is never replaced. Non-NC items deduct nothing
     * (matching the existing rule that only NC items subtract points).
     */
    public static function itemDeduction(VisitorVisitItem $item): int
    {
        if ($item->status !== 'nc') {
            return 0;
        }

        $deduction = (int) $item->deduction_score;

        if ($item->isCritical()) {
            $deduction += self::criticalDeduction();
        }

        return $deduction;
    }

    /**
     * Raw SQL SUM expression for the ORIGINAL item deduction only (the sum of
     * deduction_score of every Non-Compliant item), with no Critical penalty.
     * Used by every aggregate path so the "before penalty" score can be shown
     * alongside the final score without duplicating the formula.
     */
    public static function originalDeductionSql(string $table = 'visitors_visit_items'): string
    {
        return "SUM(CASE WHEN {$table}.status = 'nc' THEN {$table}.deduction_score ELSE 0 END)";
    }

    /**
     * Raw SQL COUNT expression for the qualifying Critical items: Non-Compliant
     * rows whose snapshot severity is Critical. Each inspection item is counted
     * exactly once; follow-ups, CAPA actions and violation links are separate
     * tables and are deliberately never joined here, so they cannot inflate the
     * penalty.
     */
    public static function criticalItemsSql(string $table = 'visitors_visit_items'): string
    {
        return "SUM(CASE WHEN {$table}.status = 'nc' AND LOWER({$table}.severity) = 'critical' THEN 1 ELSE 0 END)";
    }

    /**
     * Raw SQL SUM expression for the same per-item deduction rule. The DB
     * aggregate paths (reports list, color filter, dashboard analytics) use
     * this instead of duplicating the formula, so every place that computes a
     * score stays consistent. Critical items keep their deduction_score and add
     * the penalty exactly once; the configured value is inlined as a plain int.
     */
    public static function deductionSql(string $table = 'visitors_visit_items'): string
    {
        $c = self::criticalDeduction();
        return "SUM(CASE WHEN {$table}.status = 'nc' THEN "
            . "({$table}.deduction_score + CASE WHEN LOWER({$table}.severity) = 'critical' THEN {$c} ELSE 0 END) "
            . "ELSE 0 END)";
    }

    /**
     * Raw SQL SUM expression for a deduction display already restricted to NC
     * rows (e.g. the per-severity chart): Critical rows add the penalty to
     * their deduction_score, other severities keep their deduction_score.
     */
    public static function deductionBySeveritySql(string $table = 'visitors_visit_items'): string
    {
        $c = self::criticalDeduction();
        return "SUM({$table}.deduction_score + CASE WHEN LOWER({$table}.severity) = 'critical' THEN {$c} ELSE 0 END)";
    }

    /**
     * The single source of truth for score calculation.
     *
     * The returned array keeps the legacy keys (deduction/final/percentage/
     * color always describe the score AFTER the additional Critical penalty)
     * and adds the before/after breakdown used by the report summary:
     *  - original_deduction: sum of the items' own deduction_score (NC only)
     *  - critical_items / critical_penalty_per_item / critical_penalty
     *  - before: the same score without the additional Critical penalty
     *
     * @return array<string,mixed>
     */
    public function calculate(VisitorVisit $visit): array
    {
        $items = $visit->items;

        $available = $items->filter(fn ($i) => $i->status !== 'na')
            ->sum('deduction_score');

        $originalDeduction = $items
            ->filter(fn ($i) => $i->status === 'nc')
            ->sum('deduction_score');

        $criticalItems = $items
            ->filter(fn ($i) => $i->status === 'nc' && $i->isCritical())
            ->count();

        return $this->fromAggregates($available, $originalDeduction, $criticalItems);
    }

    /**
     * The single source of truth for turning aggregated available/original
     * deduction/qualifying Critical item counts into the before and after
     * score, percentage and color. Report listing and dashboard analytics use
     * this with database aggregates so they never duplicate the formula.
     *
     * The additional Critical penalty is always read from
     * config('inspection.critical_deduction') and added ON TOP of the original
     * item deductions; the available score is never changed by the penalty.
     *
     * @return array<string,mixed>
     */
    public function fromAggregates(int $available, int $originalDeduction, int $criticalItems = 0): array
    {
        $criticalItems = max(0, $criticalItems);
        $penaltyPerItem = self::criticalDeduction();
        $criticalPenalty = $criticalItems * $penaltyPerItem;
        $deduction = $originalDeduction + $criticalPenalty;

        $beforeFinal = $available - $originalDeduction;
        $final = $available - $deduction;

        $beforePercentage = $available > 0 ? round(($beforeFinal / $available) * 100, 1) : null;
        $percentage = $available > 0 ? round(($final / $available) * 100, 1) : null;

        $beforeColor = $this->colorFor($beforePercentage);

        return [
            // Legacy keys: always the score AFTER the Critical penalty.
            'available' => $available,
            'deduction' => $deduction,
            'final' => $final,
            'percentage' => $percentage,
            'color' => $this->colorFor($percentage),

            // Original item deductions (before the additional penalty).
            'original_deduction' => $originalDeduction,

            // Additional Critical penalty breakdown.
            'critical_items' => $criticalItems,
            'critical_penalty_per_item' => $penaltyPerItem,
            'critical_penalty' => $criticalPenalty,

            // The score as it would be without the additional Critical penalty.
            'before' => [
                'deduction' => $originalDeduction,
                'final' => $beforeFinal,
                'percentage' => $beforePercentage,
                'color' => $beforeColor,
            ],
        ];
    }

    public function colorFor(?float $percentage): string
    {
        if ($percentage === null) return 'slate';
        if ($percentage >= 85) return 'blue';
        if ($percentage >= 75) return 'green';
        if ($percentage >= 68) return 'yellow';
        return 'red';
    }

    /**
     * Count items reviewed, based on visited_at.
     */
    public function reviewedCount(VisitorVisit $visit): int
    {
        return $visit->items->filter->isReviewed()->count();
    }

    /**
     * Per-section reviewed/total counters based on visited_at.
     */
    public function sectionProgress(VisitorVisit $visit): Collection
    {
        return $visit->items
            ->groupBy('section_name')
            ->map(function (Collection $group) {
                $total = $group->count();
                $reviewed = $group->filter->isReviewed()->count();
                return (object) ['total' => $total, 'reviewed' => $reviewed];
            });
    }
}
