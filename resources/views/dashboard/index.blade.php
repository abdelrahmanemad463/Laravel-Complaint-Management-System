@extends('layouts.app')
@section('content')
@php
    $filters = $filters ?? [];
    $filterData = $filterData ?? [];
    $total = $kpis['total'] ?? 0;
    $dashboardQuery = array_filter($filters, static fn ($value) => $value !== null && $value !== '' && $value !== []);
    $selectedBranchIds = array_map('intval', $filters['branch_ids'] ?? []);
    $statusByKey = collect($filterData['statuses'] ?? [])->keyBy(fn ($status) => strtolower(trim($status->name_en)));
    $priorityByKey = collect($filterData['priorities'] ?? [])->keyBy(fn ($priority) => strtolower(trim($priority->name_en)));
    $formatDuration = static function (?int $minutes): string {
        if ($minutes === null) return __('common.no_resolution_data');
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        return $hours > 0 ? $hours.'h '.$remaining.'m' : $remaining.'m';
    };
    $insightText = static function (array $insight): string {
        return match ($insight['key']) {
            'top_branch' => __('common.top_branch_insight', ['name' => $insight['name']]),
            'top_service' => __('common.top_service_insight', ['name' => $insight['name'], 'percentage' => $insight['percentage']]),
            'critical_attention' => __('common.critical_attention_insight', ['total' => $insight['total']]),
            'volume_up' => __('common.volume_increased', ['percentage' => $insight['percentage']]),
            'volume_down' => __('common.volume_decreased', ['percentage' => $insight['percentage']]),
            'top_category' => __('common.top_category_insight', ['name' => $insight['name']]),
            'pending_attention' => __('common.pending_attention_insight', ['total' => $insight['total']]),
            default => '',
        };
    };
@endphp

<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="eyebrow">{{ __('common.overview') }}</p>
        <h1 class="page-title">{{ __('common.dashboard') }}</h1>
        <p class="page-subtitle">{{ __('common.dashboard_subtitle') }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('complaints.index', $dashboardQuery) }}" class="btn-secondary">{{ __('common.view_all_complaints') }}</a>
        <a href="{{ route('complaints.create') }}" class="btn-primary">{{ __('common.new_complaint') }}</a>
    </div>
</div>

<form method="GET" action="{{ route('dashboard') }}" class="card mb-6 space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="section-title">{{ __('common.dashboard_filters') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $total }} {{ __('common.complaints_count') }} · {{ $period['from'] }} → {{ $period['to'] }}</p>
        </div>
        <div class="text-sm text-slate-500">{{ __('common.resolution_definition') }}</div>
    </div>
    <div>
        <p class="form-label">{{ __('common.date_presets') }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach($presets as $preset)
                <a href="{{ route('dashboard', array_merge($dashboardQuery, ['date_from' => $preset['from'], 'date_to' => $preset['to']])) }}" class="btn-small">{{ __('common.'.$preset['key']) }}</a>
            @endforeach
        </div>
    </div>
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="form-label">{{ __('common.date_from') }}</label>
            <input class="form-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label class="form-label">{{ __('common.date_to') }}</label>
            <input class="form-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="relative" data-branch-picker data-filter-dropdown data-search-url="{{ route('complaints.branches.search') }}" data-empty-text="{{ __('common.no_branch_matches') }}" data-selected-ids="{{ implode(',', $selectedBranchIds) }}" data-select-label="{{ __('common.select') }}" data-singular-label="{{ __('common.branch') }}" data-plural-label="{{ __('common.branches') }}">
            <label class="form-label">{{ __('common.branch') }}</label>
            <div id="selected-branch-inputs"></div>
            <button type="button" class="form-input flex items-center justify-between gap-3 text-start" data-picker-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="branch-results">
                <span id="branch-summary" class="truncate text-slate-500">{{ __('common.select') }}</span>
                <span class="shrink-0 text-slate-500" aria-hidden="true">▾</span>
            </button>
            <div id="branch-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden w-full min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-lg" role="listbox" aria-multiselectable="true" aria-label="{{ __('common.branch') }}">
                <div class="border-b border-slate-200 p-1">
                    <input type="search" id="branch-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_branches') }}" data-picker-search>
                </div>
                <div class="max-h-56 space-y-1 overflow-y-auto p-1" data-picker-options>
                    @forelse($filterData['branches'] ?? [] as $branch)
                        <button type="button" class="branch-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $branch->id }}" data-name="{{ $branch->localized_name }}" data-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? '1' : '0' }}" role="option" aria-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? 'true' : 'false' }}">
                            <span class="min-w-0 truncate font-medium">{{ $branch->localized_name }}</span>
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-300 text-xs text-indigo-600"><span class="branch-check" aria-hidden="true">{{ in_array($branch->id, $selectedBranchIds, true) ? '✓' : '' }}</span></span>
                        </button>
                    @empty
                        <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_branch_matches') }}</p>
                    @endforelse
                </div>
                <button type="button" class="hidden w-full rounded-md px-3 py-2 text-start text-sm font-semibold text-indigo-700 hover:bg-indigo-50" data-picker-clear>{{ __('common.clear') }}</button>
            </div>
        </div>
        @foreach(['services'=>'service_id','categories'=>'category_id','sources'=>'source_id','types'=>'type_id','priorities'=>'priority_id','statuses'=>'status_id'] as $key => $field)
            <div>
                <label class="form-label">{{ __('common.'.$key) }}</label>
                <select class="form-input" name="{{ $field }}">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($filterData[$key] ?? [] as $item)
                        <option value="{{ $item->id }}" @selected(($filters[$field] ?? null) == $item->id)>{{ $item->localized_name }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <button class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.apply_filters') }}</button>
        <a class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ route('dashboard') }}">{{ __('common.reset') }}</a>
    </div>
</form>

<div class="mb-6 grid gap-3 sm:gap-4 grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 xl:grid-cols-6">
    @php($pendingId = $statusByKey['pending']->id ?? null)
    @php($inProgressId = $statusByKey['in progress']->id ?? null)
    @php($solvedId = $statusByKey['solved']->id ?? null)
    <a href="{{ route('complaints.index', $dashboardQuery) }}" class="card dashboard-kpi border-slate-200 border-l-4 border-l-slate-500 p-5">
        <span class="text-sm font-semibold text-slate-500">{{ __('common.total_complaints') }}</span>
        <strong class="mt-2 block text-3xl tracking-tight">{{ number_format($kpis['total']) }}</strong>
        @if($comparison['value'] !== null)<span class="mt-2 inline-flex text-xs font-semibold {{ $comparison['direction'] === 'up' ? 'text-rose-600' : 'text-emerald-600' }}">{{ $comparison['direction'] === 'up' ? '↑' : '↓' }} {{ $comparison['value'] }}% {{ __('common.vs_previous_period') }}</span>@endif
    </a>
    <a href="{{ route('complaints.index', array_merge($dashboardQuery, $pendingId ? ['status_id' => $pendingId] : [])) }}" class="card dashboard-kpi border-slate-200 border-l-4 border-l-amber-500 p-5">
        <span class="text-sm font-semibold text-slate-500">{{ __('common.pending') }}</span>
        <strong class="mt-2 block text-3xl tracking-tight">{{ number_format($kpis['pending']) }}</strong>
    </a>
    <a href="{{ route('complaints.index', array_merge($dashboardQuery, $inProgressId ? ['status_id' => $inProgressId] : [])) }}" class="card dashboard-kpi border-slate-200 border-l-4 border-l-indigo-500 p-5">
        <span class="text-sm font-semibold text-slate-500">{{ __('common.in_progress') }}</span>
        <strong class="mt-2 block text-3xl tracking-tight">{{ number_format($kpis['in_progress']) }}</strong>
    </a>
    <a href="{{ route('complaints.index', array_merge($dashboardQuery, $solvedId ? ['status_id' => $solvedId] : [])) }}" class="card dashboard-kpi border-slate-200 border-l-4 border-l-emerald-500 p-5">
        <span class="text-sm font-semibold text-slate-500">{{ __('common.solved') }}</span>
        <strong class="mt-2 block text-3xl tracking-tight">{{ number_format($kpis['solved']) }}</strong>
    </a>
    <a href="{{ route('complaints.index', $dashboardQuery) }}" class="card dashboard-kpi border-slate-200 border-l-4 border-l-rose-500 p-5">
        <span class="text-sm font-semibold text-slate-500">{{ __('common.high_critical') }}</span>
        <strong class="mt-2 block text-3xl tracking-tight">{{ number_format($kpis['high_critical']) }}</strong>
    </a>
    <div class="card dashboard-kpi border-slate-200 border-l-4 border-l-cyan-500 p-5">
        <span class="text-sm font-semibold text-slate-500">{{ __('common.resolution_rate') }}</span>
        <strong class="mt-2 block text-3xl tracking-tight">{{ number_format($kpis['resolution_rate'], 1) }}%</strong>
        <span class="mt-2 block text-xs text-slate-500">{{ __('common.resolution_definition') }}</span>
    </div>
</div>

<div class="mb-6 grid gap-6 grid-cols-1 lg:grid-cols-3">
    <div class="card p-4 sm:p-6 lg:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="section-title">{{ __('common.complaints_over_time') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('common.trend_grouping', ['grouping' => __('common.'.$period['grouping'])]) }}</p>
            </div>
            <span class="badge" style="--badge-color:#4f46e5">{{ $period['days'] }} {{ __('common.date') }}</span>
        </div>
        <div class="mt-5 h-64 sm:h-72">@if($total > 0)<canvas id="trend-chart"></canvas>@else<div class="empty flex h-full items-center justify-center">{{ __('common.no_dashboard_data') }}</div>@endif</div>
    </div>
    <div class="card p-4 sm:p-6">
        <h2 class="section-title">{{ __('common.resolution_performance') }}</h2>
        <div class="mt-6 flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-5">
            <div class="relative flex h-32 w-32 shrink-0 items-center justify-center rounded-full" style="background:conic-gradient(#10b981 {{ $kpis['resolution_rate'] }}%, #e2e8f0 0)">
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-white text-2xl font-bold text-slate-900">{{ number_format($kpis['resolution_rate'], 1) }}%</div>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-700">{{ __('common.resolved') }}: {{ number_format($kpis['solved']) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('common.average_resolution_time') }}: <strong class="text-slate-900">{{ $formatDuration($kpis['average_resolution_minutes']) }}</strong></p>
                <p class="mt-2 text-xs text-slate-500">{{ __('common.average_resolution_time_hint') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
    @foreach([
        ['key' => 'branches', 'title' => 'complaints_by_branch', 'type' => 'bar'],
        ['key' => 'categories', 'title' => 'complaints_by_category', 'type' => 'bar'],
        ['key' => 'types', 'title' => 'complaints_by_type', 'type' => 'bar'],
        ['key' => 'services', 'title' => 'complaints_by_service', 'type' => 'bar'],
        ['key' => 'sources', 'title' => 'complaints_by_source', 'type' => 'doughnut'],
        ['key' => 'statuses', 'title' => 'complaints_by_status', 'type' => 'doughnut'],
        ['key' => 'priorities', 'title' => 'priority_distribution', 'type' => 'doughnut'],
    ] as $chartCard)
        <div class="card p-4 sm:p-6">
            <h2 class="section-title">{{ __('common.'.$chartCard['title']) }}</h2>
            <div class="mt-5 h-64 sm:h-72">@if($total > 0 && !empty($charts[$chartCard['key']]))<canvas id="{{ $chartCard['key'] }}-chart" data-chart-type="{{ $chartCard['type'] }}"></canvas>@else<div class="empty flex h-full items-center justify-center">{{ __('common.no_dashboard_data') }}</div>@endif</div>
        </div>
    @endforeach
</div>

<div class="card mb-6 overflow-hidden p-0">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 sm:p-6">
        <div><h2 class="section-title">{{ __('common.branch_performance') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('common.complaints_by_branch') }}</p></div>
        @if($total > 0)<span class="badge" style="--badge-color:#0891b2">{{ count($branchRows) }} {{ __('common.branches') }}</span>@endif
    </div>
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="data-table">
            <thead><tr><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.rank') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.branch') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.total') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.percentage') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.resolved') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.pending') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.high_critical') }}</th><th class="px-3 sm:px-4 lg:px-6 text-xs sm:text-sm">{{ __('common.resolution_rate') }}</th></tr></thead>
            <tbody>
            @forelse($branchRows as $row)
                <tr>
                    <td>{{ $row['rank'] }}</td>
                    <td><a class="font-semibold text-indigo-700 hover:underline" href="{{ route('complaints.index', array_merge($dashboardQuery, ['branch_ids' => [$row['id']]])) }}">{{ $row['name'] }}</a></td>
                    <td class="font-semibold">{{ number_format($row['total']) }}</td><td>{{ number_format($row['percentage'], 1) }}%</td><td>{{ number_format($row['resolved']) }}</td><td>{{ number_format($row['pending']) }}</td><td>{{ number_format($row['high_critical']) }}</td><td>{{ number_format($row['resolution_rate'], 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">{{ __('common.no_dashboard_data') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mb-6 grid gap-6 grid-cols-1 lg:grid-cols-3">
    <div class="card p-4 sm:p-6 lg:col-span-2">
        <div class="flex items-center justify-between gap-3"><h2 class="section-title">{{ __('common.recent_complaints') }}</h2><a class="back-link" href="{{ route('complaints.index', $dashboardQuery) }}">{{ __('common.view_all') }}</a></div>
        <div class="mt-5 divide-y divide-slate-200">
            @forelse($recentComplaints as $complaint)
                <a href="{{ route('complaints.show', $complaint) }}" class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0 hover:text-indigo-700">
                    <div><p class="font-semibold">#{{ $complaint->id }} · {{ $complaint->customer?->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $complaint->branch?->localized_name }} · {{ $complaint->type?->localized_name }}</p></div>
                    <div class="text-end"><span class="badge" style="--badge-color:{{ $complaint->priority?->color ?? '#64748b' }}">{{ $complaint->priority?->localized_name }}</span><p class="mt-1 text-xs text-slate-500">{{ $complaint->complaint_date?->translatedFormat('d M Y') }}</p></div>
                </a>
            @empty
                <p class="empty px-0">{{ __('common.no_recent_complaints') }}</p>
            @endforelse
        </div>
    </div>
    <div class="card p-4 sm:p-6">
        <div class="flex items-center justify-between gap-3"><h2 class="section-title">{{ __('common.attention_required') }}</h2><a class="back-link" href="{{ route('complaints.index', $dashboardQuery) }}">{{ __('common.view_all') }}</a></div>
        <div class="mt-5 space-y-3">
            @forelse($attentionComplaints as $complaint)
                <a href="{{ route('complaints.show', $complaint) }}" class="block rounded-lg border border-rose-200 p-3 transition hover:bg-rose-50">
                    <div class="flex items-center justify-between gap-3"><strong>#{{ $complaint->id }}</strong><span class="badge" style="--badge-color:{{ $complaint->priority?->color ?? '#dc2626' }}">{{ $complaint->priority?->localized_name }}</span></div>
                    <p class="mt-1 text-sm text-slate-600">{{ $complaint->branch?->localized_name }} · {{ $complaint->type?->localized_name }}</p>
                </a>
            @empty
                <p class="empty px-0">{{ __('common.no_dashboard_data') }}</p>
            @endforelse
        </div>
    </div>
</div>

<div class="mb-6 grid gap-6 grid-cols-1 lg:grid-cols-2">
    <div class="card p-4 sm:p-6">
        <div class="flex items-center justify-between gap-3"><h2 class="section-title">{{ __('common.recently_solved') }}</h2><a class="back-link" href="{{ route('complaints.index', $dashboardQuery) }}">{{ __('common.view_all') }}</a></div>
        <div class="mt-5 space-y-3">
            @forelse($recentlySolved as $complaint)
                <a href="{{ route('complaints.show', $complaint) }}" class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 p-3 hover:bg-emerald-50"><div><strong>#{{ $complaint->id }} · {{ $complaint->customer?->name }}</strong><p class="mt-1 text-sm text-slate-500">{{ $complaint->branch?->localized_name }}</p></div><span class="text-xs text-slate-500">{{ $complaint->resolved_at?->translatedFormat('d M Y H:i') }}</span></a>
            @empty
                <p class="empty px-0">{{ __('common.no_dashboard_data') }}</p>
            @endforelse
        </div>
    </div>
    <div class="card p-4 sm:p-6">
        <h2 class="section-title">{{ __('common.insights') }}</h2>
        <div class="mt-5 space-y-3">
            @forelse($insights as $insight)
                <div class="rounded-lg border border-indigo-200 bg-indigo-50/60 p-3 text-sm text-indigo-900">{{ $insightText($insight) }}</div>
            @empty
                <p class="empty px-0">{{ __('common.no_dashboard_data') }}</p>
            @endforelse
        </div>
    </div>
</div>

<script id="dashboard-chart-data" type="application/json">@json(['charts' => $charts, 'locale' => app()->getLocale()])</script>
@endsection
