<?php

namespace App\Services\Visitors;

use App\Models\VisitorVisit;
use Illuminate\Support\Collection;

class VisitScoreService
{
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

        $deduction = $items->filter(fn ($i) => $i->status === 'nc')
            ->sum('deduction_score');

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
