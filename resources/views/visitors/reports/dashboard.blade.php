@extends('layouts.app')
@section('content')
@php
$d = $data;
$f = $d['filters'];
$cards = $d['cards'];
$scoreHex = $cards['avgScore'] === null ? '#475569' : ($cards['avgScore'] >= 85 ? '#2563eb' : ($cards['avgScore'] >= 75 ? '#16a34a' : ($cards['avgScore'] >= 68 ? '#ca8a04' : '#dc2626')));
$charts = [
    'branch' => array_map(fn ($r) => ['name' => $r['name'], 'total' => $r['score'] ?? 0, 'color' => $r['color'] ?? '#4f46e5'], $d['branchComparison']),
    'severity' => array_map(fn ($r) => ['name' => ucfirst($r['name']), 'total' => $r['count'], 'color' => $r['color']], $d['severity']),
    'section' => array_map(fn ($r) => ['name' => $r['name'], 'total' => $r['count'], 'color' => '#4f46e5'], $d['section']),
    'rootCause' => array_map(fn ($r) => ['name' => $r['name'], 'total' => $r['count'], 'color' => '#0891b2'], $d['rootCause']),
    'trend' => ['labels' => array_map(fn ($r) => $r['label'], $d['trend']), 'values' => array_map(fn ($r) => $r['score'], $d['trend'])],
    'dueStatus' => array_map(fn ($r) => ['name' => __("visitors.st_{$r['name']}"), 'total' => $r['count'], 'color' => $r['color']], $d['dueStatusChart']),
];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('visitors.home') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
        <h1 class="page-title mt-4">{{ __('visitors.report_dashboard') }}</h1>
        <p class="page-subtitle">{{ __('visitors.report_dashboard_subtitle') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('visitors.reports') }}" class="btn-secondary">{{ __('visitors.reports') }}</a>
        @can('visit.create')<a href="{{ route('visitors.create') }}" class="btn-primary">{{ __('visitors.new_visit') }}</a>@endcan
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('visitors.reports.dashboard') }}" class="card mb-6 p-4 sm:p-6">
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-8">
        <div><label class="form-label">{{ __('common.date_from') }}</label><input type="date" name="date_from" value="{{ $f['date_from'] ?? '' }}" class="form-input"></div>
        <div><label class="form-label">{{ __('common.date_to') }}</label><input type="date" name="date_to" value="{{ $f['date_to'] ?? '' }}" class="form-input"></div>
        <div><label class="form-label">{{ __('common.branch') }}</label>
            <select name="branch_id" class="form-input"><option value="">{{ __('common.all') }}</option>
            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(($f['branch_id'] ?? null) == $branch->id)>{{ $branch->localized_name }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label">{{ __('visitors.visit_type') }}</label>
            <select name="visit_type_id" class="form-input"><option value="">{{ __('common.all') }}</option>
            @foreach($visitTypes as $type)<option value="{{ $type->id }}" @selected(($f['visit_type_id'] ?? null) == $type->id)>{{ $type->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label">{{ __('visitors.inspector') }}</label>
            <select name="inspector_id" class="form-input"><option value="">{{ __('common.all') }}</option>
            @foreach($inspectors as $inspector)<option value="{{ $inspector->id }}" @selected(($f['inspector_id'] ?? null) == $inspector->id)>{{ $inspector->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label">{{ __('visitors.severity') }}</label>
            <select name="severity" class="form-input"><option value="">{{ __('common.all') }}</option>
            @foreach($severityOptions as $s)<option value="{{ $s }}" @selected(($f['severity'] ?? null) === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label">{{ __('visitors.section') }}</label>
            <select name="section" class="form-input"><option value="">{{ __('common.all') }}</option>
            @foreach($sections as $section)<option value="{{ $section->name }}" @selected(($f['section'] ?? null) === $section->name)>{{ $section->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label">{{ __('visitors.trend_grouping') }}</label>
            <select name="grouping" class="form-input">
                @foreach(['day'=>'Day','week'=>'Week','month'=>'Month'] as $k => $g)<option value="{{ $k }}" @selected(($f['grouping'] ?? 'week') === $k)>{{ __("common.$g") }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <button type="submit" class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.apply_filters') }}</button>
        <a href="{{ route('visitors.reports.dashboard') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('common.clear') }}</a>
    </div>
</form>

{{-- Cards --}}
<div class="mb-8 grid gap-3 sm:gap-4 grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.total_visits') }}</div><div class="mt-1 text-3xl font-black text-slate-900">{{ $cards['totalVisits'] }}</div></div>
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.avg_score') }}</div><div class="mt-1 text-3xl font-black" style="color:{{ $scoreHex }}">{{ $cards['avgScore'] !== null ? $cards['avgScore'].'%' : '—' }}</div></div>
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.critical_violations') }}</div><div class="mt-1 text-3xl font-black {{ $cards['criticalViolations'] ? 'text-rose-600' : 'text-slate-900' }}">{{ $cards['criticalViolations'] }}</div></div>
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.total_violations') }}</div><div class="mt-1 text-3xl font-black text-orange-600">{{ $cards['totalViolations'] }}</div></div>
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.open_capa') }}</div><div class="mt-1 text-3xl font-black text-blue-600">{{ $cards['openCapa'] }}</div></div>
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.pending_review_capa') }}</div><div class="mt-1 text-3xl font-black text-amber-600">{{ $cards['pendingReviewCapa'] }}</div></div>
    <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.overdue_capa') }}</div><div class="mt-1 text-3xl font-black {{ $cards['overdueCapa'] ? 'text-rose-600' : 'text-slate-900' }}">{{ $cards['overdueCapa'] }}</div></div>
</div>

{{-- Corrective Actions / Due Dates section --}}
@php $due = $d['dueCards']; @endphp
<div class="mb-8">
    <h3 class="mb-3 text-lg font-bold text-slate-900">{{ __('visitors.corrective_action_plan') }} — {{ __('visitors.filter_due_status') }}</h3>
    <div class="mb-6 grid gap-3 sm:gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8">
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.open_actions') }}</div><div class="mt-1 text-2xl font-black text-blue-600">{{ $due['open'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.st_upcoming') }}</div><div class="mt-1 text-2xl font-black text-indigo-600">{{ $due['upcoming'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.due_soon_actions') }}</div><div class="mt-1 text-2xl font-black {{ $due['dueSoon'] ? 'text-amber-600' : 'text-slate-900' }}">{{ $due['dueSoon'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.overdue_actions') }}</div><div class="mt-1 text-2xl font-black {{ $due['overdue'] ? 'text-rose-600' : 'text-slate-900' }}">{{ $due['overdue'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.immediate_actions') }}</div><div class="mt-1 text-2xl font-black {{ $due['immediate'] ? 'text-violet-600' : 'text-slate-900' }}">{{ $due['immediate'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.closed_actions') }}</div><div class="mt-1 text-2xl font-black text-emerald-600">{{ $due['closed'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.closed_late_actions') }}</div><div class="mt-1 text-2xl font-black {{ $due['closedLate'] ? 'text-orange-600' : 'text-slate-900' }}">{{ $due['closedLate'] }}</div></div>
        <div class="card text-center"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.completion_rate') }}</div><div class="mt-1 text-2xl font-black text-slate-900">{{ $due['completionRate'] !== null ? $due['completionRate'].'%' : '—' }}</div></div>
    </div>

    <div class="grid gap-6 grid-cols-1 lg:grid-cols-2">
        <div class="card p-4 sm:p-6"><h3 class="section-title mb-4">{{ __('visitors.violations_by_severity') }} <span class="text-xs font-normal text-slate-400">({{ __('visitors.filter_due_status') }})</span></h3><div class="h-64 sm:h-72"><canvas id="report-dueStatus-chart"></canvas></div></div>
        <div class="card overflow-x-auto p-4 sm:p-6">
            <h3 class="section-title mb-4">{{ __('visitors.branch_comparison') }} — {{ __('visitors.open_actions') }}</h3>
            @if($d['branchDueAnalysis'])
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2">{{ __('common.branch') }}</th><th class="py-2">{{ __('visitors.open_actions') }}</th><th class="py-2">{{ __('visitors.overdue_actions') }}</th><th class="py-2">{{ __('visitors.closed_actions') }}</th><th class="py-2">{{ __('visitors.completion_rate') }}</th></tr></thead>
                <tbody>
                    @foreach($d['branchDueAnalysis'] as $row)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 font-semibold">{{ $row['name'] }}</td>
                        <td class="py-2 {{ $row['open'] ? 'font-bold text-blue-600' : 'text-slate-500' }}">{{ $row['open'] }}</td>
                        <td class="py-2 {{ $row['overdue'] ? 'font-bold text-rose-600' : 'text-slate-500' }}">{{ $row['overdue'] }}</td>
                        <td class="py-2 text-emerald-600">{{ $row['closed'] }}</td>
                        <td class="py-2 font-semibold">{{ $row['completionRate'] !== null ? $row['completionRate'].'%' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-slate-500">{{ __('visitors.no_data') }}</p>
            @endif
        </div>
    </div>
</div>

<script type="application/json" id="visit-report-chart-data">{!! json_encode(['charts' => $charts]) !!}</script>

{{-- Charts --}}
<div class="mb-8 grid gap-6 grid-cols-1 lg:grid-cols-2">
    <div class="card p-4 sm:p-6"><h3 class="section-title mb-4">{{ __('visitors.branch_performance') }}</h3><div class="h-64 sm:h-72"><canvas id="report-branch-chart"></canvas></div></div>
    <div class="card p-4 sm:p-6"><h3 class="section-title mb-4">{{ __('visitors.violations_by_severity') }}</h3><div class="h-64 sm:h-72"><canvas id="report-severity-chart"></canvas></div></div>
    <div class="card p-4 sm:p-6"><h3 class="section-title mb-4">{{ __('visitors.violations_by_section') }}</h3><div class="h-64 sm:h-72"><canvas id="report-section-chart"></canvas></div></div>
    <div class="card p-4 sm:p-6"><h3 class="section-title mb-4">{{ __('visitors.root_cause_distribution') }}</h3><div class="h-64 sm:h-72"><canvas id="report-rootCause-chart"></canvas></div></div>
</div>

{{-- Score trend --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.score_trend') }}</h3>
    <div class="h-64 sm:h-72"><canvas id="report-trend-chart"></canvas></div>
</div>

{{-- Branch comparison + best/worst --}}
<div class="mb-8 grid gap-6 grid-cols-1 lg:grid-cols-3">
    <div class="card lg:col-span-2 overflow-x-auto p-4 sm:p-6">
        <h3 class="section-title mb-4">{{ __('visitors.branch_comparison') }}</h3>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2">{{ __('common.branch') }}</th><th class="py-2">{{ __('visitors.avg_score') }}</th><th class="py-2">{{ __('visitors.visits') }}</th><th class="py-2">{{ __('visitors.violations') }}</th></tr></thead>
            <tbody>
                @foreach($d['branchComparison'] as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 font-semibold">{{ $row['name'] }}</td>
                    <td class="py-2"><span class="badge" style="--badge-color:{{ $row['color'] }}">{{ $row['score'] !== null ? $row['score'].'%' : 'â€”' }}</span></td>
                    <td class="py-2">{{ $row['visits'] }}</td>
                    <td class="py-2 {{ $row['violations'] ? 'font-bold text-rose-600' : 'text-slate-500' }}">{{ $row['violations'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="space-y-6">
        <div class="card">
            <h3 class="section-title mb-3">{{ __('visitors.best_branches') }}</h3>
            @forelse($d['bestBranches'] as $row)
            <div class="flex items-center justify-between py-1 text-sm"><span class="font-medium">{{ $row['name'] }}</span><span class="flex items-center gap-2 text-emerald-600"><span class="text-xs text-slate-500">{{ $row['visits'] }} {{ __('visitors.visits') }}</span><b>{{ $row['score'] }}%</b></span></div>
            @empty
            <p class="text-sm text-slate-500">{{ __('visitors.no_data') }}</p>
            @endforelse
        </div>
        <div class="card">
            <h3 class="section-title mb-3">{{ __('visitors.worst_branches') }}</h3>
            @forelse($d['worstBranches'] as $row)
            <div class="flex items-center justify-between py-1 text-sm"><span class="font-medium">{{ $row['name'] }}</span><span class="flex items-center gap-2 text-rose-600"><span class="text-xs text-slate-500">{{ $row['visits'] }} {{ __('visitors.visits') }}</span><b>{{ $row['score'] }}%</b></span></div>
            @empty
            <p class="text-sm text-slate-500">{{ __('visitors.no_data') }}</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Recurring + critical violations --}}
<div class="mb-8 grid gap-6 grid-cols-1 lg:grid-cols-2">
    <div class="card overflow-x-auto p-4 sm:p-6">
        <h3 class="section-title mb-4">{{ __('visitors.most_repeated_violations') }}</h3>
        @if($d['recurring'])
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2">{{ __('common.code') }}</th><th class="py-2">{{ __('visitors.item') }}</th><th class="py-2">{{ __('visitors.occurrences') }}</th></tr></thead>
            <tbody>
                @foreach($d['recurring'] as $row)
                <tr class="border-b border-slate-100"><td class="py-2"><span class="badge" style="--badge-color:#475569">{{ $row['item_code'] }}</span></td><td class="py-2 font-medium">{{ $row['item_title'] }}</td><td class="py-2 font-bold text-rose-600">{{ $row['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-slate-500">{{ __('visitors.no_data') }}</p>
        @endif
    </div>
    <div class="card overflow-x-auto p-4 sm:p-6">
        <h3 class="section-title mb-4">{{ __('visitors.critical_violations') }}</h3>
        @if($d['criticalViolations'])
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2">{{ __('common.branch') }}</th><th class="py-2">{{ __('visitors.visit') }}</th><th class="py-2">{{ __('visitors.date') }}</th><th class="py-2">{{ __('visitors.item') }}</th><th class="py-2">{{ __('visitors.inspector') }}</th></tr></thead>
            <tbody>
                @foreach($d['criticalViolations'] as $row)
                <tr class="border-b border-slate-100"><td class="py-2">{{ $row['branch_name'] }}</td><td class="py-2"><a class="text-indigo-700 underline" href="{{ route('visitors.reports.show', $row['visit_id']) }}">#{{ $row['visit_id'] }}</a></td><td class="py-2">{{ \Carbon\Carbon::parse($row['visit_date'])->format('d/m/Y') }}</td><td class="py-2 font-medium">{{ $row['item_code'] }} â€” {{ $row['item_title'] }}</td><td class="py-2">{{ $row['inspector_name'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-slate-500">{{ __('visitors.no_critical_violations') }}</p>
        @endif
    </div>
</div>

{{-- CAPA analytics + inspector performance --}}
<div class="mb-8 grid gap-6 grid-cols-1 lg:grid-cols-2">
    <div class="card p-4 sm:p-6">
        <h3 class="section-title mb-4">{{ __('visitors.capa_analytics') }}</h3>
        <div class="grid grid-cols-1 xs:grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border p-3 text-center"><div class="text-xs font-bold uppercase text-blue-600">{{ __('visitors.capa_open') }}</div><div class="text-2xl font-black">{{ $d['capa']['open'] }}</div></div>
            <div class="rounded-xl border p-3 text-center"><div class="text-xs font-bold uppercase text-violet-600">{{ __('visitors.capa_in_progress') }}</div><div class="text-2xl font-black">{{ $d['capa']['in_progress'] }}</div></div>
            <div class="rounded-xl border p-3 text-center"><div class="text-xs font-bold uppercase text-rose-600">{{ __('visitors.capa_overdue') }}</div><div class="text-2xl font-black">{{ $d['capa']['overdue'] }}</div></div>
            <div class="rounded-xl border p-3 text-center"><div class="text-xs font-bold uppercase text-emerald-600">{{ __('visitors.capa_closed') }}</div><div class="text-2xl font-black">{{ $d['capa']['closed'] }}</div></div>
            <div class="rounded-xl border p-3 text-center"><div class="text-xs font-bold uppercase text-slate-600">{{ __('visitors.capa_rejected') }}</div><div class="text-2xl font-black">{{ $d['capa']['rejected'] }}</div></div>
            <div class="rounded-xl border p-3 text-center"><div class="text-xs font-bold uppercase text-slate-500">{{ __('visitors.avg_time_to_close') }}</div><div class="text-2xl font-black">{{ $d['averageTimeToCloseCapa'] !== null ? $d['averageTimeToCloseCapa'].'d' : 'â€”' }}</div></div>
        </div>
    </div>
    <div class="card overflow-x-auto p-4 sm:p-6">
        <h3 class="section-title mb-4">{{ __('visitors.inspector_performance') }}</h3>
        @if($d['inspectorPerformance'])
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2">{{ __('visitors.inspector') }}</th><th class="py-2">{{ __('visitors.visits') }}</th><th class="py-2">{{ __('visitors.avg_score') }}</th><th class="py-2">{{ __('visitors.violations_found') }}</th></tr></thead>
            <tbody>
                @foreach($d['inspectorPerformance'] as $row)
                <tr class="border-b border-slate-100"><td class="py-2 font-semibold">{{ $row['name'] }}</td><td class="py-2">{{ $row['visits'] }}</td><td class="py-2 font-bold">{{ $row['score'] !== null ? $row['score'].'%' : 'â€”' }}</td><td class="py-2 {{ $row['violations'] ? 'font-bold text-rose-600' : '' }}">{{ $row['violations'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-slate-500">{{ __('visitors.no_data') }}</p>
        @endif
    </div>
</div>
@endsection
