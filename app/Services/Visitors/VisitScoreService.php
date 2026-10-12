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
     * @return array{available:int,deduction:int,final:int,percentage:?float,color:string}
     */
    public function calculate(VisitorVisit $visit): array
    {
        $items = $visit->items;

        $available = $items->filter(fn ($i) => $i->status !== 'na')
            ->sum('deduction_score');

        $deduction = $items->sum(fn ($i) => self::itemDeduction($i));

        return $this->fromAggregates($available, $deduction);
    }

    /**
     * The single source of truth for turning aggregated available/deduction
     * scores into the final score, percentage and color. Report listing uses
     * this with database aggregates so it never duplicates the formula.
     *
     * @return array{available:int,deduction:int,final:int,percentage:?float,color:string}
     */
    public function fromAggregates(int $available, int $deduction): array
    {
        $final = $available - $deduction;

        $percentage = $available > 0 ? round(($final / $available) * 100, 1) : null;

        return [
            'available' => $available,
            'deduction' => $deduction,
            'final' => $final,
            'percentage' => $percentage,
            'color' => $this->colorFor($percentage),
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
