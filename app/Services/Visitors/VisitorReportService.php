<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use App\Models\VisitorViolationFollowUp;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use App\Services\Visitors\DueDateService;
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
            'followUps.visitItem', 'followUps.performer', 'followUps.photos', 'followUps.violations',
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
            'followUps' => $this->followUps($visit),
            'dueSummary' => $this->dueSummary($items),
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
     * evidence photos and CAPA status + due info. The action's due status is
     * resolved centrally via DueDateService, never duplicated here.
     */
    private function violations(VisitorVisit $visit): Collection
    {
        return $visit->items
            ->where('status', 'nc')
            ->values()
            ->map(function ($item) {
                $action = $item->capaAction;
                return (object) [
                    'item' => $item,
                    'hasAction' => $action !== null,
                    'dueStatus' => $action ? $action->dueStatus() : null,
                    'effectiveStatus' => $action ? $action->effectiveStatus() : null,
                    'periodLabel' => $action ? $action->periodLabel() : ($item->period_hours !== null ? DueDateService::hoursLabel($item->period_hours) : null),
                    'dueAt' => $action?->due_at,
                ];
            });
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
                'dueStatus' => $action->dueStatus(),
                'periodLabel' => $action->periodLabel(),
            ];
        });

        $statusCounts = $actions->groupBy('status')
            ->map->count()
            ->all();

        $dueCounts = $actions->groupBy('dueStatus')
            ->map->count()
            ->all();

        return [
            'actions' => $actions,
            'statusCounts' => $statusCounts,
            'dueCounts' => $dueCounts,
        ];
    }

    /**
     * Follow-up records: previous violations followed up during this visit.
     */
    private function followUps(VisitorVisit $visit): Collection
    {
        return $visit->followUps
            ->sortByDesc('followed_up_at')
            ->values()
            ->map(function (VisitorViolationFollowUp $fu) {
                return (object) [
                    'followUp' => $fu,
                    'item' => $fu->visitItem,
                ];
            });
    }

    /**
     * Score-summary "Due / action items" counts derived from the CAPA actions
     * of the visit (open/overdue/due-soon/immediate/closed/closed-late).
     */
    private function dueSummary(Collection $items): array
    {
        $actions = $items->pluck('capaAction')->filter();

        $open = 0;
        $dueSoon = 0;
        $overdue = 0;
        $immediate = 0;
        $closed = 0;
        $closedLate = 0;

        foreach ($actions as $action) {
            if ($action->status === 'rejected') {
                continue;
            }
            if ($action->status === 'closed') {
                $closed++;
                if ($action->due_at !== null && $action->completed_at !== null && $action->completed_at->gt($action->due_at)) {
                    $closedLate++;
                }
                continue;
            }
            // open / in_progress
            $open++;
            $dueStatus = $action->dueStatus();
            if ($dueStatus === 'immediate') {
                $immediate++;
            } elseif ($dueStatus === 'overdue') {
                $overdue++;
            } elseif ($dueStatus === 'due_soon') {
                $dueSoon++;
            }
        }

        return [
            'openActions' => $open,
            'dueSoon' => $dueSoon,
            'overdue' => $overdue,
            'immediate' => $immediate,
            'closed' => $closed,
            'closedLate' => $closedLate,
            'upcoming' => $open - ($immediate + $overdue + $dueSoon),
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

        if (!empty($filters['due_status']) && in_array($filters['due_status'], ['open', 'overdue', 'due_soon', 'immediate', 'closed_late', 'closed'], true)) {
            $base->whereIn('id', $this->dueStatusSubquery($filters['due_status']));
        }

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
            ->selectRaw("SUM(CASE WHEN status = 'nc' AND severity = 'critical' THEN 1 ELSE 0 END) as critical_violations")
            ->whereIn('visit_id', $ids)
            ->groupBy('visit_id')
            ->get()
            ->keyBy('visit_id');

        $capaRows = VisitorCapaAction::query()
            ->select('visit_id')
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->selectRaw("SUM(CASE WHEN status IN ('open','in_progress') THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->whereIn('visit_id', $ids)
            ->groupBy('visit_id')
            ->get()
            ->keyBy('visit_id');

        $visits->getCollection()->transform(function ($visit) use ($rows, $capaRows) {
            $row = $rows->get($visit->id);
            $available = $row ? (int) $row->available : 0;
            $deduction = $row ? (int) $row->deduction : 0;
            $visit->setAttribute('violations', $row ? (int) $row->violations : 0);
            $visit->setAttribute('critical_violations', $row ? (int) $row->critical_violations : 0);
            $visit->setAttribute('score', $this->score->fromAggregates($available, $deduction));
            $visit->setAttribute('capa_due', $this->capaDueStats((int) $visit->id, $capaRows->get($visit->id)));
            return $visit;
        });
    }

    /**
     * Per-visit CAPA due counts for the reports table. Effective overdue/due-soon
     * counts rely on the stored due_at timestamps held by each open action.
     */
    private function capaDueStats(int $visitId, $agg): array
    {
        $open = $agg ? (int) $agg->open_count : 0;
        $closed = $agg ? (int) $agg->closed : 0;

        if ($open === 0) {
            return ['open' => 0, 'due_soon' => 0, 'overdue' => 0, 'immediate' => 0, 'closed' => $closed, 'closed_late' => 0, 'completed' => $closed];
        }

        $openActions = VisitorCapaAction::query()
            ->where('visit_id', $visitId)
            ->whereIn('status', ['open', 'in_progress'])
            ->get(['period_hours', 'due_at', 'completed_at', 'status']);

        $overdue = 0;
        $dueSoon = 0;
        $immediate = 0;
        foreach ($openActions as $action) {
            $status = $action->dueStatus();
            if ($status === 'immediate') {
                $immediate++;
            } elseif ($status === 'overdue') {
                $overdue++;
            } elseif ($status === 'due_soon') {
                $dueSoon++;
            }
        }

        $closedLate = 0;
        if ($closed > 0) {
            $closedLates = VisitorCapaAction::query()
                ->where('visit_id', $visitId)
                ->where('status', 'closed')
                ->whereNotNull('due_at')
                ->whereNotNull('completed_at')
                ->whereColumn('completed_at', '>', 'due_at')
                ->count();
            $closedLate = (int) $closedLates;
        }

        return [
            'open' => $open,
            'due_soon' => $dueSoon,
            'overdue' => $overdue,
            'immediate' => $immediate,
            'closed' => $closed,
            'closed_late' => $closedLate,
            'completed' => $closed - $closedLate,
        ];
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

    /**
     * Visit ids that have at least one CAPA action matching the requested due
     * status. Open/due-soon/immediate split the open actions by their stored
     * due_at; overdue relies on elapsed time; closed_late compares completed_at
     * against due_at.
     */
    private function dueStatusSubquery(string $status)
    {
        $base = VisitorCapaAction::query()->select('visit_id');

        return match ($status) {
            'overdue' => (clone $base)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', now()->toDateTimeString()),
            'due_soon' => (clone $base)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->whereRaw('due_at BETWEEN ? AND ?', [now()->toDateTimeString(), now()->addHours((float) config('visitors.due_soon_hours', 24))->toDateTimeString()]),
            'immediate' => (clone $base)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNull('due_at'),
            'open' => (clone $base)->whereIn('status', ['open', 'in_progress']),
            'closed' => (clone $base)->where('status', 'closed'),
            'closed_late' => (clone $base)
                ->where('status', 'closed')
                ->whereNotNull('due_at')
                ->whereNotNull('completed_at')
                ->whereColumn('completed_at', '>', 'due_at'),
            default => (clone $base)->whereRaw('1 = 0'),
        };
    }
}
