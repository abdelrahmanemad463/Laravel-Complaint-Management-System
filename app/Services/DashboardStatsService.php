<?php

namespace App\Services;

use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Priority, Service};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    private const DEFAULT_DAYS = 30;

    public function build(array $filters): array
    {
        $today = now()->startOfDay();
        $from = Carbon::parse($filters['date_from'] ?? $today->copy()->subDays(self::DEFAULT_DAYS - 1)->toDateString())->startOfDay();
        $to = Carbon::parse($filters['date_to'] ?? $today->toDateString())->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $normalizedFilters = array_merge($filters, [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
        ]);
        $currentQuery = Complaint::query()->filter($normalizedFilters);
        $statuses = ComplaintStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color', 'sort_order']);
        $priorities = Priority::query()->orderBy('level')->orderBy('name')->get(['id', 'name', 'color', 'level', 'sort_order']);
        $resolvedStatusIds = $statuses->filter(fn ($status) => in_array($this->key($status->name), ['solved', 'closed'], true))->pluck('id')->all();
        $pendingStatusIds = $statuses->filter(fn ($status) => $this->key($status->name) === 'pending')->pluck('id')->all();
        $highCriticalPriorityIds = $priorities->filter(fn ($priority) => in_array($this->key($priority->name), ['high', 'critical'], true))->pluck('id')->all();

        $total = (clone $currentQuery)->count();
        $statusCounts = $this->countsById($currentQuery, 'status_id');
        $priorityCounts = $this->countsById($currentQuery, 'priority_id');
        $resolved = $this->sumIds($statusCounts, $resolvedStatusIds);
        $pending = $this->sumIds($statusCounts, $pendingStatusIds);
        $inProgress = $this->sumNamed($statuses, $statusCounts, 'in progress');
        $highCritical = $this->sumIds($priorityCounts, $highCriticalPriorityIds);
        $resolutionRate = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;

        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($from->diffInDays($to))->startOfDay();
        $previousFilters = array_merge($normalizedFilters, [
            'date_from' => $previousFrom->toDateString(),
            'date_to' => $previousTo->toDateString(),
        ]);
        $previousTotal = Complaint::query()->filter($previousFilters)->count();

        return [
            'filters' => $normalizedFilters,
            'filterData' => $this->filterData(array_map('intval', $normalizedFilters['branch_ids'] ?? [])),
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $from->diffInDays($to) + 1,
                'grouping' => $this->grouping($from, $to),
            ],
            'presets' => $this->presets($today),
            'kpis' => [
                'total' => $total,
                'pending' => $pending,
                'in_progress' => $inProgress,
                'solved' => $resolved,
                'high_critical' => $highCritical,
                'resolution_rate' => $resolutionRate,
                'average_resolution_minutes' => $this->averageResolutionMinutes($currentQuery),
            ],
            'comparison' => $this->comparison($total, $previousTotal),
            'charts' => [
                'trend' => $this->trend($currentQuery, $from, $to),
                'branches' => $this->dimensionData($currentQuery, 'branch_id', Branch::class),
                'categories' => $this->dimensionData($currentQuery, 'category_id', ComplaintCategory::class),
                'types' => $this->dimensionData($currentQuery, 'type_id', ComplaintType::class),
                'services' => $this->dimensionData($currentQuery, 'service_id', Service::class),
                'sources' => $this->dimensionData($currentQuery, 'source_id', ComplaintSource::class),
                'statuses' => $this->dimensionData($currentQuery, 'status_id', ComplaintStatus::class),
                'priorities' => $this->dimensionData($currentQuery, 'priority_id', Priority::class),
            ],
            'branchRows' => $this->branchRows($currentQuery, $total, $resolvedStatusIds, $pendingStatusIds, $highCriticalPriorityIds),
            'recentComplaints' => (clone $currentQuery)->with(['customer', 'branch', 'type', 'priority', 'status'])->latest('complaint_date')->latest('created_at')->limit(6)->get(),
            'attentionComplaints' => $this->attentionComplaints($currentQuery, $resolvedStatusIds, $highCriticalPriorityIds),
            'recentlySolved' => $this->recentlySolved($currentQuery, $resolvedStatusIds),
            'insights' => $this->insights($total, $previousTotal, $resolutionRate, $highCritical, $pending, $this->dimensionData($currentQuery, 'branch_id', Branch::class), $this->dimensionData($currentQuery, 'category_id', ComplaintCategory::class), $this->dimensionData($currentQuery, 'service_id', Service::class)),
        ];
    }

    private function filterData(array $selectedBranchIds = []): array
    {
        $branches = Branch::query()->orderBy('name')->limit(5)->get(['id', 'name']);
        $missingBranchIds = array_diff($selectedBranchIds, $branches->pluck('id')->all());
        if ($missingBranchIds !== []) {
            $branches = $branches->concat(
                Branch::query()->whereIn('id', $missingBranchIds)->orderBy('name')->get(['id', 'name'])
            );
        }

        return [
            'branches' => $branches,
            'services' => Service::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'categories' => ComplaintCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'sources' => ComplaintSource::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'types' => ComplaintType::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'statuses' => ComplaintStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function presets(Carbon $today): array
    {
        $lastMonth = $today->copy()->subMonthNoOverflow();

        return [
            ['key' => 'today', 'from' => $today->toDateString(), 'to' => $today->toDateString()],
            ['key' => 'yesterday', 'from' => $today->copy()->subDay()->toDateString(), 'to' => $today->copy()->subDay()->toDateString()],
            ['key' => 'last_7_days', 'from' => $today->copy()->subDays(6)->toDateString(), 'to' => $today->toDateString()],
            ['key' => 'last_30_days', 'from' => $today->copy()->subDays(29)->toDateString(), 'to' => $today->toDateString()],
            ['key' => 'this_month', 'from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->toDateString()],
            ['key' => 'last_month', 'from' => $lastMonth->copy()->startOfMonth()->toDateString(), 'to' => $lastMonth->copy()->endOfMonth()->toDateString()],
            ['key' => 'this_year', 'from' => $today->copy()->startOfYear()->toDateString(), 'to' => $today->toDateString()],
        ];
    }

    private function countsById(Builder $query, string $column): Collection
    {
        return (clone $query)
            ->select($column, DB::raw('COUNT(*) AS total'))
            ->groupBy($column)
            ->pluck('total', $column);
    }

    private function dimensionData(Builder $query, string $column, string $model): array
    {
        $counts = $this->countsById($query, $column);
        $records = $model::query()->get(['id', 'name'])->keyBy('id');
        $palette = ['#4f46e5', '#0891b2', '#16a34a', '#ea580c', '#dc2626', '#9333ea', '#ca8a04', '#0f766e'];

        return $records->map(function ($record) use ($counts, $palette) {
            $total = (int) ($counts[$record->id] ?? 0);
            return [
                'id' => $record->id,
                'name' => $record->name,
                'total' => $total,
                'color' => $record->color ?: $palette[$record->id % count($palette)],
            ];
        })->filter(fn ($row) => $row['total'] > 0)->sortByDesc('total')->values()->map(function ($row) use ($query) {
            return $row;
        })->all();
    }

    private function branchRows(Builder $query, int $total, array $resolvedIds, array $pendingIds, array $highCriticalIds): array
    {
        $rows = (clone $query)
            ->select('branch_id', DB::raw('COUNT(*) AS total'))
            ->selectRaw($this->sumCase('status_id', $resolvedIds).' AS resolved_total')
            ->selectRaw($this->sumCase('status_id', $pendingIds).' AS pending_total')
            ->selectRaw($this->sumCase('priority_id', $highCriticalIds).' AS high_critical_total')
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->get();
        $branches = Branch::query()->whereIn('id', $rows->pluck('branch_id'))->get(['id', 'name'])->keyBy('id');

        return $rows->map(function ($row, $index) use ($branches, $total) {
            $count = (int) $row->total;
            return [
                'rank' => $index + 1,
                'id' => (int) $row->branch_id,
                'name' => $branches[$row->branch_id]->name ?? __('common.unknown'),
                'total' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'resolved' => (int) $row->resolved_total,
                'pending' => (int) $row->pending_total,
                'high_critical' => (int) $row->high_critical_total,
                'resolution_rate' => $count > 0 ? round(((int) $row->resolved_total / $count) * 100, 1) : 0,
            ];
        })->all();
    }

    private function attentionComplaints(Builder $query, array $resolvedIds, array $highCriticalIds): Collection
    {
        return (clone $query)
            ->when($highCriticalIds, fn ($q) => $q->whereIn('priority_id', $highCriticalIds))
            ->when($resolvedIds, fn ($q) => $q->whereNotIn('status_id', $resolvedIds))
            ->with(['branch', 'type', 'priority', 'status'])
            ->latest('complaint_date')
            ->latest('created_at')
            ->limit(6)
            ->get();
    }

    private function recentlySolved(Builder $query, array $resolvedIds): Collection
    {
        if (!$resolvedIds) {
            return collect();
        }

        return (clone $query)
            ->whereIn('status_id', $resolvedIds)
            ->whereNotNull('resolved_at')
            ->with(['customer', 'branch', 'resolver'])
            ->latest('resolved_at')
            ->limit(6)
            ->get();
    }

    private function averageResolutionMinutes(Builder $query): ?int
    {
        $totalMinutes = 0;
        $count = 0;
        foreach ((clone $query)->whereNotNull('resolved_at')->whereNotNull('created_at')->select(['created_at', 'resolved_at'])->cursor() as $complaint) {
            $minutes = Carbon::parse($complaint->created_at)->diffInMinutes(Carbon::parse($complaint->resolved_at), false);
            if ($minutes >= 0) {
                $totalMinutes += $minutes;
                $count++;
            }
        }

        return $count > 0 ? (int) round($totalMinutes / $count) : null;
    }

    private function trend(Builder $query, Carbon $from, Carbon $to): array
    {
        $grouping = $this->grouping($from, $to);
        $dateExpression = $this->trendDateExpression();
        $daily = (clone $query)
            ->selectRaw($dateExpression.' AS bucket_date, COUNT(*) AS total')
            ->groupByRaw($dateExpression)
            ->orderByRaw($dateExpression.' ASC')
            ->pluck('total', 'bucket_date');
        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $this->bucketKey($cursor, $grouping);
            $buckets[$key] ??= 0;
            $buckets[$key] += (int) ($daily[(string) $cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        return [
            'labels' => array_keys($buckets),
            'values' => array_values($buckets),
            'grouping' => $grouping,
        ];
    }

    private function trendDateExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlsrv'
            ? 'CAST(complaint_date AS date)'
            : 'DATE(complaint_date)';
    }

    private function grouping(Carbon $from, Carbon $to): string
    {
        $days = $from->diffInDays($to) + 1;
        return $days <= 31 ? 'day' : ($days <= 180 ? 'week' : 'month');
    }

    private function bucketKey(Carbon $date, string $grouping): string
    {
        return match ($grouping) {
            'week' => $date->copy()->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }

    private function comparison(int $current, int $previous): array
    {
        if ($previous === 0) {
            return ['value' => null, 'direction' => $current > 0 ? 'up' : 'flat'];
        }

        $change = round((($current - $previous) / $previous) * 100, 1);
        return ['value' => abs($change), 'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat')];
    }

    private function insights(int $total, int $previousTotal, float $resolutionRate, int $highCritical, int $pending, array $branches, array $categories, array $services): array
    {
        $insights = [];
        if ($branches[0] ?? null) {
            $insights[] = ['key' => 'top_branch', 'name' => $branches[0]['name'], 'total' => $branches[0]['total']];
        }
        if ($services[0] ?? null) {
            $insights[] = ['key' => 'top_service', 'name' => $services[0]['name'], 'percentage' => $total > 0 ? round(($services[0]['total'] / $total) * 100, 1) : 0];
        }
        if ($highCritical > 0) {
            $insights[] = ['key' => 'critical_attention', 'total' => $highCritical];
        }
        if ($previousTotal > 0 && $total !== $previousTotal) {
            $insights[] = ['key' => $total > $previousTotal ? 'volume_up' : 'volume_down', 'percentage' => abs(round((($total - $previousTotal) / $previousTotal) * 100, 1))];
        }
        if ($categories[0] ?? null) {
            $insights[] = ['key' => 'top_category', 'name' => $categories[0]['name']];
        }
        if ($pending > 0 && count($insights) < 5) {
            $insights[] = ['key' => 'pending_attention', 'total' => $pending];
        }

        return array_slice($insights, 0, 5);
    }

    private function sumCase(string $column, array $ids): string
    {
        $values = $ids ? implode(',', array_map('intval', $ids)) : '0';
        return "SUM(CASE WHEN {$column} IN ({$values}) THEN 1 ELSE 0 END)";
    }

    private function sumIds(Collection $counts, array $ids): int
    {
        return array_sum(array_map(fn ($id) => (int) ($counts[$id] ?? 0), $ids));
    }

    private function sumNamed(Collection $records, Collection $counts, string $name): int
    {
        $id = $records->first(fn ($record) => $this->key($record->name) === $name)?->id;
        return $id ? (int) ($counts[$id] ?? 0) : 0;
    }

    private function key(string $value): string
    {
        return strtolower(trim($value));
    }
}
