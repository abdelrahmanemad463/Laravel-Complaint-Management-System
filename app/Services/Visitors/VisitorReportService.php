<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the complete dataset for a single inspection report. This is the
 * single source of truth shared by the web report, the print view, and the
 * PDF service so the report logic is never duplicated.
 *
 * Historical values come only from the visitors_visit_items snapshot and the
 * score always comes from VisitScoreService.
 */
class VisitorReportService
{
    public function __construct(
        private VisitScoreService $score,
    ) {}

    public function build(VisitorVisit $visit): array
    {
        $visit->load([
            'visitType', 'branch', 'inspector',
            'items.rootCause', 'items.photos', 'items.capaAction',
            'capaActions.visitItem.rootCause', 'capaActions.responsible', 'capaActions.updates',
        ]);

        $items = $visit->items;
        $score = $this->score->calculate($visit);

        return [
            'visit' => $visit,
            'score' => $score,
            'counts' => $this->statusCounts($items),
            'severity' => $this->severityTotals($items),
            'sections' => $this->sectionPerformance($items),
            'rootCauses' => $this->rootCauseCounts($items),
            'violations' => $this->violations($visit),
            'capa' => $this->capa($visit),
        ];
    }

    private function statusCounts(Collection $items): array
    {
        return [
            'ok' => $items->where('status', 'ok')->count(),
            'nc' => $items->where('status', 'nc')->count(),
            'na' => $items->where('status', 'na')->count(),
        ];
    }

    /**
     * Non-compliant items grouped by severity (snapshot), with count and total
     * deduction. NA items never count toward violations.
     */
    private function severityTotals(Collection $items): Collection
    {
        return $items
            ->where('status', 'nc')
            ->groupBy('severity')
            ->map(function (Collection $group, $severity) {
                return (object) [
                    'severity' => $severity,
                    'count' => $group->count(),
                    'deduction' => $group->sum('deduction_score'),
                ];
            })
            ->values();
    }

    /**
     * Per-section performance using the snapshot section_name. NA items are
     * excluded from the compliance denominator and never counted as
     * non-compliant, matching the official scoring rules.
     */
    private function sectionPerformance(Collection $items): Collection
    {
        return $items
            ->groupBy('section_name')
            ->map(function (Collection $group) {
                $total = $group->count();
                $ok = $group->where('status', 'ok')->count();
                $nc = $group->where('status', 'nc')->count();
                $na = $group->where('status', 'na')->count();
                $applicable = $total - $na;
                return (object) [
                    'section' => $group->first()->section_name,
                    'total' => $total,
                    'ok' => $ok,
                    'nc' => $nc,
                    'na' => $na,
                    'applicable' => $applicable,
                    'compliance' => $applicable > 0 ? round(($ok / $applicable) * 100) : null,
                    'deduction' => $group->where('status', 'nc')->sum('deduction_score'),
                ];
            })
            ->values();
    }

    /**
     * Root cause distribution. Only counts NC items that actually have a root
     * cause.
     */
    private function rootCauseCounts(Collection $items): Collection
    {
        return $items
            ->where('status', 'nc')
            ->filter(fn ($i) => $i->rootCause !== null)
            ->groupBy(fn ($i) => $i->rootCause->name)
            ->map(fn (Collection $group, $name) => (object) [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Violation details: only non-compliant items, with root cause, notes,
     * evidence photos and CAPA status.
     */
    private function violations(VisitorVisit $visit): Collection
    {
        return $visit->items
            ->where('status', 'nc')
            ->values();
    }

    /**
     * CAPA summary for the Corrective Action Plan and status analytics.
     */
    private function capa(VisitorVisit $visit): array
    {
        $actions = $visit->capaActions->map(function (VisitorCapaAction $action) {
            return (object) [
                'action' => $action,
                'status' => $action->effectiveStatus(),
            ];
        });

        $statusCounts = $actions->groupBy('status')
            ->map->count()
            ->all();

        return [
            'actions' => $actions,
            'statusCounts' => $statusCounts,
        ];
    }

    /**
     * Paginated, DB-filtered list of completed reports with their score and
     * violation count computed from database aggregates (no N+1, no loading
     * the whole table into memory).
     */
    public function listReports(array $filters): LengthAwarePaginator
    {
        $base = VisitorVisit::query()
            ->with(['branch', 'visitType', 'inspector'])
            ->completed()
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($filters['visit_type_id'] ?? null, fn ($q, $v) => $q->where('visit_type_id', $v))
            ->when($filters['inspector_id'] ?? null, fn ($q, $v) => $q->where('inspector_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('visit_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('visit_date', '<=', $v));

        if (!empty($filters['colors'])) {
            $colors = array_values(array_intersect($filters['colors'], ['blue', 'green', 'yellow', 'red']));
            if ($colors) {
                $base->where(function ($q) use ($colors) {
                    foreach ($colors as $color) {
                        $q->orWhereIn('id', $this->colorSubquery($color));
                    }
                });
            }
        }

        $visits = $base->latest('visit_date')->paginate(15)->withQueryString();

        $this->attachScoresAndViolations($visits);

        return $visits;
    }

    private function attachScoresAndViolations(LengthAwarePaginator $visits): void
    {
        $ids = $visits->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        $rows = VisitorVisitItem::query()
            ->select('visit_id')
            ->selectRaw("SUM(CASE WHEN status <> 'na' THEN deduction_score ELSE 0 END) as available")
            ->selectRaw("SUM(CASE WHEN status = 'nc' THEN deduction_score ELSE 0 END) as deduction")
            ->selectRaw("SUM(CASE WHEN status = 'nc' THEN 1 ELSE 0 END) as violations")
            ->whereIn('visit_id', $ids)
            ->groupBy('visit_id')
            ->get()
            ->keyBy('visit_id');

        $visits->getCollection()->transform(function ($visit) use ($rows) {
            $row = $rows->get($visit->id);
            $available = $row ? (int) $row->available : 0;
            $deduction = $row ? (int) $row->deduction : 0;
            $visit->setAttribute('violations', $row ? (int) $row->violations : 0);
            $visit->setAttribute('score', $this->score->fromAggregates($available, $deduction));
            return $visit;
        });
    }

    /**
     * Subquery selecting visit ids whose score falls into the given color band.
     * The percentage formula lives only here and in VisitScoreService.
     */
    private function colorSubquery(string $color)
    {
        $availExpr = "SUM(CASE WHEN status <> 'na' THEN deduction_score ELSE 0 END)";
        $dedExpr = "SUM(CASE WHEN status = 'nc' THEN deduction_score ELSE 0 END)";
        $pct = "($availExpr - $dedExpr) * 100.0 / $availExpr";

        $sub = VisitorVisitItem::query()
            ->select('visit_id')
            ->groupBy('visit_id');

        // SQL Server cannot reference the select aliases inside HAVING, so the
        // whole expression is repeated here (mirrors VisitScoreService formula).
        return match ($color) {
            'blue' => (clone $sub)->havingRaw("$pct >= 85"),
            'green' => (clone $sub)->havingRaw("$pct >= 75 AND $pct < 85"),
            'yellow' => (clone $sub)->havingRaw("$pct >= 68 AND $pct < 75"),
            'red' => (clone $sub)->havingRaw("$pct < 68"),
            default => (clone $sub)->havingRaw('1 = 0'),
        };
    }
}
