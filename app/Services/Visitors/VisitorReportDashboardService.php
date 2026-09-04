<?php

namespace App\Services\Visitors;

use App\Models\VisitorVisit;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates completed inspections across many visits using SQL so large
 * datasets never need to be loaded into PHP. Charts consume the returned
 * arrays directly.
 */
class VisitorReportDashboardService
{
    private const SEVERITY_COLORS = [
        'critical' => '#dc2626',
        'major' => '#ea580c',
        'minor' => '#16a34a',
    ];

    /**
     * Build everything the dashboard needs for the given filters.
     */
    public function build(array $filters): array
    {
        $f = $this->normalize($filters);
        $comparison = $this->branchComparison($f);
        $bestWorst = $this->bestAndWorstBranches($comparison);

        return [
            'filters' => $f,
            'cards' => $this->cards($f),
            'dueCards' => $this->dueCards($f),
            'averageTimeToCloseCapa' => $this->averageTimeToCloseCapa($f),
            'severity' => $this->severityDistribution($f),
            'section' => $this->sectionViolations($f),
            'rootCause' => $this->rootCauseDistribution($f),
            'branchComparison' => $comparison,
            'bestBranches' => $bestWorst['best'],
            'worstBranches' => $bestWorst['worst'],
            'recurring' => $this->recurringViolations($f),
            'criticalViolations' => $this->criticalViolations($f),
            'capa' => $this->capaAnalytics($f),
            'dueStatusChart' => $this->dueStatusDistribution($f),
            'branchDueAnalysis' => $this->branchDueAnalysis($f),
            'inspectorPerformance' => $this->inspectorPerformance($f),
            'trend' => $this->scoreTrend($f),
        ];
    }

    private function normalize(array $filters): array
    {
        return [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'branch_id' => $filters['branch_id'] ?? null,
            'visit_type_id' => $filters['visit_type_id'] ?? null,
            'inspector_id' => $filters['inspector_id'] ?? null,
            'severity' => $filters['severity'] ?? null,
            'section' => $filters['section'] ?? null,
            'grouping' => in_array($filters['grouping'] ?? null, ['day', 'week', 'month'], true) ? $filters['grouping'] : 'week',
        ];
    }

    /**
     * Base item-level query restricted to completed visits within the filters.
     */
    private function itemsQuery(array $f)
    {
        return \App\Models\VisitorVisitItem::query()
            ->join('visitors_visits', 'visitors_visits.id', '=', 'visitors_visit_items.visit_id')
            ->where('visitors_visits.status', 'completed')
            ->when($f['date_from'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '>=', $v))
            ->when($f['date_to'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '<=', $v))
            ->when($f['branch_id'], fn ($q, $v) => $q->where('visitors_visits.branch_id', $v))
            ->when($f['visit_type_id'], fn ($q, $v) => $q->where('visitors_visits.visit_type_id', $v))
            ->when($f['inspector_id'], fn ($q, $v) => $q->where('visitors_visits.inspector_id', $v))
            ->when($f['severity'], fn ($q, $v) => $q->where('visitors_visit_items.severity', $v))
            ->when($f['section'], fn ($q, $v) => $q->where('visitors_visit_items.section_name', $v));
    }

    private function cards(array $f): array
    {
        $base = $this->itemsQuery($f);
        $row = (clone $base)
            ->selectRaw('COUNT(DISTINCT visitors_visits.id) as visits')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN 1 ELSE 0 END) as violations')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' AND visitors_visit_items.severity = \'critical\' THEN 1 ELSE 0 END) as critical')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status <> \'na\' THEN visitors_visit_items.deduction_score ELSE 0 END) as avail')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN visitors_visit_items.deduction_score ELSE 0 END) as ded')
            ->first();

        $avail = (int) ($row->avail ?? 0);
        $ded = (int) ($row->ded ?? 0);
        $avgScore = $avail > 0 ? round((($avail - $ded) / $avail) * 100, 1) : null;

        return [
            'totalVisits' => (int) ($row->visits ?? 0),
            'totalViolations' => (int) ($row->violations ?? 0),
            'criticalViolations' => (int) ($row->critical ?? 0),
            'avgScore' => $avgScore,
            'openCapa' => $this->countCapa($f, 'open'),
            'overdueCapa' => $this->countCapa($f, 'overdue'),
        ];
    }

    private function countCapa(array $f, string $kind): int
    {
        $q = DB::table('visitors_capa_actions')
            ->join('visitors_visits', 'visitors_visits.id', '=', 'visitors_capa_actions.visit_id')
            ->where('visitors_visits.status', 'completed')
            ->when($f['date_from'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '>=', $v))
            ->when($f['date_to'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '<=', $v))
            ->when($f['branch_id'], fn ($q, $v) => $q->where('visitors_visits.branch_id', $v))
            ->when($f['visit_type_id'], fn ($q, $v) => $q->where('visitors_visits.visit_type_id', $v))
            ->when($f['inspector_id'], fn ($q, $v) => $q->where('visitors_visits.inspector_id', $v))
            ->whereIn('visitors_capa_actions.status', ['open', 'in_progress']);

        if ($kind === 'overdue') {
            $q->whereNotNull('visitors_capa_actions.due_at')
                ->where('visitors_capa_actions.due_at', '<', now()->toDateTimeString());
        } else {
            $q->where(function ($q) {
                $q->whereNull('visitors_capa_actions.due_at')
                    ->orWhere('visitors_capa_actions.due_at', '>=', now()->toDateTimeString());
            });
        }

        return (int) $q->count();
    }

    /**
     * Cards for the Corrective Actions / Due Dates section: open, due-soon,
     * overdue, immediate, closed-late counts and the completion rate.
     */
    private function dueCards(array $f): array
    {
        $base = $this->capaQueryBase($f);
        $now = now()->toDateTimeString();
        $soon = now()->addHours((float) config('visitors.due_soon_hours', 24))->toDateTimeString();

        $row = (clone $base)
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status IN (\'open\',\'in_progress\') THEN 1 ELSE 0 END) as open_count')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status = \'closed\' THEN 1 ELSE 0 END) as closed_count')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status = \'rejected\' THEN 1 ELSE 0 END) as rejected_count')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status IN (\'open\',\'in_progress\') AND visitors_capa_actions.due_at IS NULL THEN 1 ELSE 0 END) as immediate_count')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status IN (\'open\',\'in_progress\') AND visitors_capa_actions.due_at IS NOT NULL AND visitors_capa_actions.due_at < ? THEN 1 ELSE 0 END) as overdue_count', [$now])
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status IN (\'open\',\'in_progress\') AND visitors_capa_actions.due_at IS NOT NULL AND visitors_capa_actions.due_at >= ? AND visitors_capa_actions.due_at < ? THEN 1 ELSE 0 END) as due_soon_count', [$now, $soon])
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status = \'closed\' AND visitors_capa_actions.completed_at IS NOT NULL AND visitors_capa_actions.due_at IS NOT NULL AND visitors_capa_actions.completed_at > visitors_capa_actions.due_at THEN 1 ELSE 0 END) as closed_late_count')
            ->first();

        $open = (int) ($row->open_count ?? 0);
        $closed = (int) ($row->closed_count ?? 0);
        $immediate = (int) ($row->immediate_count ?? 0);
        $overdue = (int) ($row->overdue_count ?? 0);
        $dueSoon = (int) ($row->due_soon_count ?? 0);
        $upcoming = max(0, $open - ($immediate + $overdue + $dueSoon));
        $closedLate = (int) ($row->closed_late_count ?? 0);

        $total = $open + $closed + (int) ($row->rejected_count ?? 0);

        return [
            'open' => $open,
            'upcoming' => $upcoming,
            'dueSoon' => $dueSoon,
            'overdue' => $overdue,
            'immediate' => $immediate,
            'closed' => $closed,
            'closedLate' => $closedLate,
            'completionRate' => $total > 0 ? round(($closed / $total) * 100, 1) : null,
        ];
    }

    /**
     * Distribution of open CAPA actions by due status, for the chart.
     */
    private function dueStatusDistribution(array $f): array
    {
        $base = $this->capaQueryBase($f);
        $now = now()->toDateTimeString();
        $soon = now()->addHours((float) config('visitors.due_soon_hours', 24))->toDateTimeString();

        $rows = (clone $base)
            ->whereIn('visitors_capa_actions.status', ['open', 'in_progress'])
            ->selectRaw('CASE WHEN visitors_capa_actions.due_at IS NULL THEN \'immediate\'
                WHEN visitors_capa_actions.due_at < ? THEN \'overdue\'
                WHEN visitors_capa_actions.due_at < ? THEN \'due_soon\'
                ELSE \'upcoming\' END as bucket', [$now, $soon])
            ->selectRaw('COUNT(*) as count')
            ->groupBy('bucket')
            ->get()
            ->pluck('count', 'bucket');

        $order = ['immediate', 'overdue', 'due_soon', 'upcoming'];
        $colors = ['immediate' => '#7c3aed', 'overdue' => '#dc2626', 'due_soon' => '#ca8a04', 'upcoming' => '#2563eb'];

        $out = [];
        foreach ($order as $bucket) {
            $out[] = [
                'name' => $bucket,
                'count' => (int) ($rows[$bucket] ?? 0),
                'color' => $colors[$bucket],
            ];
        }

        return $out;
    }

    /**
     * Open/overdue by branch for the branch analysis table.
     */
    private function branchDueAnalysis(array $f): array
    {
        $now = now()->toDateTimeString();

        return $this->capaQueryBase($f)
            ->leftJoin('branches', 'branches.id', '=', 'visitors_visits.branch_id')
            ->select('branches.name as branch_name', 'visitors_visits.branch_id')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status IN (\'open\',\'in_progress\') THEN 1 ELSE 0 END) as open_count')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status IN (\'open\',\'in_progress\') AND visitors_capa_actions.due_at IS NOT NULL AND visitors_capa_actions.due_at < ? THEN 1 ELSE 0 END) as overdue_count', [$now])
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status = \'closed\' THEN 1 ELSE 0 END) as closed_count')
            ->selectRaw('SUM(CASE WHEN visitors_capa_actions.status = \'closed\' AND visitors_capa_actions.completed_at IS NOT NULL AND visitors_capa_actions.due_at IS NOT NULL AND visitors_capa_actions.completed_at > visitors_capa_actions.due_at THEN 1 ELSE 0 END) as closed_late_count')
            ->groupBy('visitors_visits.branch_id', 'branches.name')
            ->orderByDesc('open_count')
            ->get()
            ->map(function ($row) {
                $open = (int) ($row->open_count ?? 0);
                $closed = (int) ($row->closed_count ?? 0);
                $total = $open + $closed;
                return [
                    'branch_id' => $row->branch_id,
                    'name' => $row->branch_name ?: ('#'.$row->branch_id),
                    'open' => $open,
                    'overdue' => (int) ($row->overdue_count ?? 0),
                    'closed' => $closed,
                    'closed_late' => (int) ($row->closed_late_count ?? 0),
                    'completionRate' => $total > 0 ? round(($closed / $total) * 100, 1) : null,
                ];
            })
            ->all();
    }

    /**
     * Shared CAPA query restricted to completed visits within the filters.
     */
    private function capaQueryBase(array $f)
    {
        return DB::table('visitors_capa_actions')
            ->join('visitors_visits', 'visitors_visits.id', '=', 'visitors_capa_actions.visit_id')
            ->where('visitors_visits.status', 'completed')
            ->when($f['date_from'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '>=', $v))
            ->when($f['date_to'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '<=', $v))
            ->when($f['branch_id'], fn ($q, $v) => $q->where('visitors_visits.branch_id', $v))
            ->when($f['visit_type_id'], fn ($q, $v) => $q->where('visitors_visits.visit_type_id', $v))
            ->when($f['inspector_id'], fn ($q, $v) => $q->where('visitors_visits.inspector_id', $v));
    }

    private function averageTimeToCloseCapa(array $f): ?float
    {
        $rows = DB::table('visitors_capa_actions')
            ->join('visitors_visits', 'visitors_visits.id', '=', 'visitors_capa_actions.visit_id')
            ->where('visitors_visits.status', 'completed')
            ->where('visitors_capa_actions.status', 'closed')
            ->whereNotNull('visitors_capa_actions.completed_at')
            ->whereNotNull('visitors_capa_actions.created_at')
            ->when($f['date_from'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '>=', $v))
            ->when($f['date_to'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '<=', $v))
            ->when($f['visit_type_id'], fn ($q, $v) => $q->where('visitors_visits.visit_type_id', $v))
            ->select('visitors_capa_actions.created_at', 'visitors_capa_actions.completed_at')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $totalDays = 0;
        foreach ($rows as $row) {
            $totalDays += \Carbon\Carbon::parse($row->completed_at)->diffInDays(\Carbon\Carbon::parse($row->created_at));
        }

        return round($totalDays / $rows->count(), 1);
    }

    private function severityDistribution(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->where('visitors_visit_items.status', 'nc')
            ->select('visitors_visit_items.severity')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(visitors_visit_items.deduction_score) as deduction')
            ->groupBy('visitors_visit_items.severity')
            ->orderByDesc('deduction')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->severity,
                'count' => (int) $row->count,
                'deduction' => (int) $row->deduction,
                'color' => self::SEVERITY_COLORS[$row->severity] ?? '#475569',
            ])
            ->all();
    }

    private function sectionViolations(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->where('visitors_visit_items.status', 'nc')
            ->select('visitors_visit_items.section_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('visitors_visit_items.section_name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['name' => $row->section_name, 'count' => (int) $row->count])
            ->all();
    }

    private function rootCauseDistribution(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->where('visitors_visit_items.status', 'nc')
            ->whereNotNull('visitors_visit_items.root_cause_id')
            ->join('visitors_root_causes', 'visitors_root_causes.id', '=', 'visitors_visit_items.root_cause_id')
            ->select('visitors_root_causes.name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('visitors_root_causes.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->all();
    }

    private function branchComparison(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->leftJoin('branches', 'branches.id', '=', 'visitors_visits.branch_id')
            ->select('visitors_visits.branch_id', 'branches.name as branch_name')
            ->selectRaw('COUNT(DISTINCT visitors_visits.id) as visits')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status <> \'na\' THEN visitors_visit_items.deduction_score ELSE 0 END) as avail')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN visitors_visit_items.deduction_score ELSE 0 END) as ded')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN 1 ELSE 0 END) as violations')
            ->groupBy('visitors_visits.branch_id', 'branches.name')
            ->orderByDesc('visits')
            ->get()
            ->map(function ($row) {
                $avail = (int) ($row->avail ?? 0);
                $ded = (int) ($row->ded ?? 0);
                $score = $avail > 0 ? round((($avail - $ded) / $avail) * 100, 1) : null;
                return [
                    'branch_id' => $row->branch_id,
                    'name' => $row->branch_name ?: ('#'.$row->branch_id),
                    'visits' => (int) $row->visits,
                    'violations' => (int) $row->violations,
                    'score' => $score,
                    'color' => $score === null ? '#475569' : ($this->color($score)),
                ];
            })
            ->all();
    }

    /**
     * Best and worst performing branches by average score, filtered to
     * branches with enough completed visits to avoid misleading one-off rows.
     */
    public function bestAndWorstBranches(array $comparison, int $minVisits = 1): array
    {
        $eligible = array_values(array_filter(
            $comparison,
            fn ($row) => $row['score'] !== null && $row['visits'] >= $minVisits
        ));
        usort($eligible, fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'best' => array_slice($eligible, 0, 5),
            'worst' => array_slice(array_reverse($eligible), 0, 5),
        ];
    }

    private function recurringViolations(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->where('visitors_visit_items.status', 'nc')
            ->select('visitors_visit_items.item_code', 'visitors_visit_items.item_title', 'visitors_visit_items.section_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('visitors_visit_items.item_code', 'visitors_visit_items.item_title', 'visitors_visit_items.section_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'item_code' => $row->item_code,
                'item_title' => $row->item_title,
                'section' => $row->section_name,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    private function criticalViolations(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->where('visitors_visit_items.status', 'nc')
            ->where('visitors_visit_items.severity', 'critical')
            ->leftJoin('branches', 'branches.id', '=', 'visitors_visits.branch_id')
            ->leftJoin('users as inspectors', 'inspectors.id', '=', 'visitors_visits.inspector_id')
            ->select(
                'visitors_visits.id as visit_id',
                'visitors_visits.visit_date',
                'branches.name as branch_name',
                'inspectors.name as inspector_name',
                'visitors_visit_items.item_code',
                'visitors_visit_items.item_title',
                'visitors_visit_items.deduction_score'
            )
            ->orderByDesc('visitors_visits.visit_date')
            ->limit(30)
            ->get()
            ->all();
    }

    private function capaAnalytics(array $f): array
    {
        $q = DB::table('visitors_capa_actions')
            ->join('visitors_visits', 'visitors_visits.id', '=', 'visitors_capa_actions.visit_id')
            ->where('visitors_visits.status', 'completed')
            ->when($f['date_from'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '>=', $v))
            ->when($f['date_to'], fn ($q, $v) => $q->whereDate('visitors_visits.visit_date', '<=', $v))
            ->when($f['visit_type_id'], fn ($q, $v) => $q->where('visitors_visits.visit_type_id', $v));

        $rows = (clone $q)
            ->select('visitors_capa_actions.status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('visitors_capa_actions.status')
            ->get()
            ->pluck('count', 'status');

        $statuses = ['open' => 0, 'in_progress' => 0, 'overdue' => 0, 'closed' => 0, 'rejected' => 0];

        foreach ($rows as $status => $count) {
            if (in_array($status, ['open', 'in_progress'], true)) {
                // overdue is derived: split stored open/in_progress by due date
                $overdue = (int) (clone $q)
                    ->where('visitors_capa_actions.status', $status)
                    ->whereNotNull('visitors_capa_actions.due_at')
                    ->where('visitors_capa_actions.due_at', '<', now()->toDateTimeString())
                    ->count();
                $open = ($count - $overdue);
                $statuses['overdue'] += $overdue;
                $statuses[$status === 'open' ? 'open' : 'in_progress'] += $open;
            } else {
                $statuses[$status] += (int) $count;
            }
        }

        return $statuses;
    }

    private function inspectorPerformance(array $f): array
    {
        return (clone $this->itemsQuery($f))
            ->leftJoin('users as inspectors', 'inspectors.id', '=', 'visitors_visits.inspector_id')
            ->select('visitors_visits.inspector_id', 'inspectors.name as inspector_name')
            ->selectRaw('COUNT(DISTINCT visitors_visits.id) as visits')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status <> \'na\' THEN visitors_visit_items.deduction_score ELSE 0 END) as avail')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN visitors_visit_items.deduction_score ELSE 0 END) as ded')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN 1 ELSE 0 END) as violations')
            ->groupBy('visitors_visits.inspector_id', 'inspectors.name')
            ->orderByDesc('visits')
            ->get()
            ->map(function ($row) {
                $avail = (int) ($row->avail ?? 0);
                $ded = (int) ($row->ded ?? 0);
                return [
                    'name' => $row->inspector_name ?: ('#'.$row->inspector_id),
                    'visits' => (int) $row->visits,
                    'score' => $avail > 0 ? round((($avail - $ded) / $avail) * 100, 1) : null,
                    'violations' => (int) $row->violations,
                ];
            })
            ->all();
    }

    private function scoreTrend(array $f): array
    {
        // One row per completed visit within the range, then bucket in PHP.
        $rows = (clone $this->itemsQuery(array_merge($f, ['severity' => null, 'section' => null])))
            ->select('visitors_visits.id as visit_id', 'visitors_visits.visit_date')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status <> \'na\' THEN visitors_visit_items.deduction_score ELSE 0 END) as avail')
            ->selectRaw('SUM(CASE WHEN visitors_visit_items.status = \'nc\' THEN visitors_visit_items.deduction_score ELSE 0 END) as ded')
            ->groupBy('visitors_visits.id', 'visitors_visits.visit_date')
            ->get();

        $bucketKey = function ($date) use ($f) {
            $d = \Carbon\Carbon::parse($date);
            return match ($f['grouping']) {
                'day' => $d->format('Y-m-d'),
                'month' => $d->format('Y-m'),
                default => $d->startOfWeek()->format('Y-m-d'),
            };
        };
        $bucketLabel = function ($key) use ($f) {
            $d = \Carbon\Carbon::parse($key);
            return match ($f['grouping']) {
                'day' => $d->format('d M Y'),
                'month' => $d->format('M Y'),
                default => $d->format('d M Y'),
            };
        };

        $buckets = [];
        foreach ($rows as $row) {
            $avail = (int) $row->avail;
            if ($avail <= 0) continue;
            $pct = (($avail - (int) $row->ded) / $avail) * 100;
            $key = $bucketKey($row->visit_date);
            $buckets[$key]['sum'] = ($buckets[$key]['sum'] ?? 0) + $pct;
            $buckets[$key]['n'] = ($buckets[$key]['n'] ?? 0) + 1;
        }

        ksort($buckets);

        $trend = [];
        foreach ($buckets as $key => $b) {
            $trend[] = [
                'label' => $bucketLabel($key),
                'score' => round($b['sum'] / $b['n'], 1),
            ];
        }

        return $trend;
    }

    private function color(float $score): string
    {
        if ($score >= 85) return 'blue';
        if ($score >= 75) return 'green';
        if ($score >= 68) return 'yellow';
        return 'red';
    }
}
