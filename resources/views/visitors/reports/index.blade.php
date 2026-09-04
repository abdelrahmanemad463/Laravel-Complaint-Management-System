@extends('layouts.app')
@section('content')
@php
$colors = [
    'blue'   => ['#2563eb', 'text-blue-700 bg-blue-100'],
    'green'  => ['#16a34a', 'text-emerald-700 bg-emerald-100'],
    'yellow' => ['#ca8a04', 'text-amber-700 bg-amber-100'],
    'red'    => ['#dc2626', 'text-rose-700 bg-rose-100'],
    'slate'  => ['#475569', 'text-slate-700 bg-slate-100'],
];
$selectedColors = $filters['colors'] ?? [];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('visitors.home') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
        <h1 class="page-title mt-4">{{ __('visitors.reports') }}</h1>
        <p class="page-subtitle">{{ __('visitors.reports_subtitle') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @can('dashboard.visitors.view')<a href="{{ route('visitors.reports.dashboard') }}" class="btn-secondary">{{ __('visitors.report_dashboard') }}</a>@endcan
        @can('visit.create')<a href="{{ route('visitors.create') }}" class="btn-primary">{{ __('visitors.new_visit') }}</a>@endcan
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('visitors.reports') }}" class="card mb-6">
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="form-label">{{ __('common.branch') }}</label>
            <select name="branch_id" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.visit_type') }}</label>
            <select name="visit_type_id" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($visitTypes as $type)
                <option value="{{ $type->id }}" @selected(($filters['visit_type_id'] ?? null) == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.inspector') }}</label>
            <select name="inspector_id" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($inspectors as $inspector)
                <option value="{{ $inspector->id }}" @selected(($filters['inspector_id'] ?? null) == $inspector->id)>{{ $inspector->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('common.date_from') }}</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-input">
        </div>
        <div>
            <label class="form-label">{{ __('common.date_to') }}</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-input">
        </div>
        <div>
            <label class="form-label">{{ __('visitors.score') }}</label>
            <div class="flex flex-wrap gap-2 pt-1">
                @foreach(['blue','green','yellow','red'] as $c)
                <label class="flex cursor-pointer items-center gap-1 text-xs font-semibold" style="color:{{ $colors[$c][0] }}">
                    <input type="checkbox" name="colors[]" value="{{ $c }}" @checked(in_array($c, $selectedColors, true)) class="h-4 w-4 rounded border-slate-300">
                    {{ __('visitors.score_class_'.$c) }}
                </label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.filter_due_status') }}</label>
            <select name="due_status" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach(['open','overdue','due_soon','immediate','closed_late','closed'] as $ds)
                <option value="{{ $ds }}" @selected(($filters['due_status'] ?? null) === $ds)>{{ __("visitors.st_$ds") }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <button type="submit" class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.apply_filters') }}</button>
        <a href="{{ route('visitors.reports') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('common.clear') }}</a>
    </div>
</form>

@if($visits->count())
<div class="card overflow-hidden !p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="w-full min-w-[1180px] text-sm text-center">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('common.complaint_id') }}</th>
                <th class="px-2 py-2.5">{{ __('common.branch') }}</th>
                <th class="px-2 py-2.5">{{ __('visitors.visit_type') }}</th>
                <th class="px-2 py-2.5">{{ __('visitors.inspector') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.visit_date') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.score') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.violations') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.critical_violations') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.open_actions') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.due_soon_actions') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.overdue_actions') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.immediate_actions') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.closed_actions') }}</th>
                <th class="px-2 py-2.5 whitespace-nowrap">{{ __('visitors.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visits as $visit)
            @php
                $score = $visit->getAttribute('score');
                [$scoreHex, $scoreClasses] = $colors[$score['color']] ?? $colors['slate'];
                $due = $visit->getAttribute('capa_due');
            @endphp
            <tr class="border-b border-slate-100 align-middle hover:bg-slate-50">
                <td class="px-2 py-2 font-semibold whitespace-nowrap text-slate-900">#{{ $visit->id }}</td>
                <td class="px-2 py-2">{{ $visit->branch?->name ?: '—' }}</td>
                <td class="px-2 py-2">{{ $visit->visitType?->name ?: '—' }}</td>
                <td class="px-2 py-2">{{ $visit->inspector?->name ?: '—' }}</td>
                <td class="px-2 py-2 whitespace-nowrap">{{ $visit->visit_date?->format('d/m/Y') }}</td>
                <td class="px-2 py-2">
                    <span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $scoreClasses }}">
                        {{ $score['percentage'] !== null ? $score['percentage'].'%' : '—' }}
                    </span>
                </td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $visit->getAttribute('violations') ? 'font-bold text-rose-600' : 'text-slate-500' }}">{{ $visit->getAttribute('violations') }}</span></td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $visit->getAttribute('critical_violations') ? 'font-bold text-rose-700' : 'text-slate-500' }}">{{ $visit->getAttribute('critical_violations') }}</span></td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $due['open'] ? 'font-bold text-blue-600' : 'text-slate-500' }}">{{ $due['open'] }}</span></td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $due['due_soon'] ? 'font-bold text-amber-600' : 'text-slate-500' }}">{{ $due['due_soon'] }}</span></td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $due['overdue'] ? 'font-bold text-rose-600' : 'text-slate-500' }}">{{ $due['overdue'] }}</span></td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $due['immediate'] ? 'font-bold text-violet-600' : 'text-slate-500' }}">{{ $due['immediate'] }}</span></td>
                <td class="px-2 py-2 tabular-nums"><span class="{{ $due['closed'] ? 'font-bold text-emerald-600' : 'text-slate-500' }}">{{ $due['closed'] }}</span></td>
                <td class="px-2 py-2">
                    <div class="flex items-center justify-center gap-2 flex-nowrap">
                        <a href="{{ route('visitors.reports.show', $visit) }}" class="btn-secondary !px-2.5 !py-1 text-[11px] min-h-[34px] inline-flex items-center whitespace-nowrap">{{ __('visitors.view_report') }}</a>
                        <a href="{{ route('visitors.reports.pdf', $visit) }}" class="btn-primary !px-2.5 !py-1 text-[11px] min-h-[34px] inline-flex items-center whitespace-nowrap">{{ __('visitors.print_pdf') }}</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $visits->links() }}</div>
@else
<div class="card text-center text-slate-500">{{ __('visitors.no_reports') }}</div>
@endif
@endsection
