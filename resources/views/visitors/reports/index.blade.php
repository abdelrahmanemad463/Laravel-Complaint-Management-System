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
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-6">
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
    </div>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <button type="submit" class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.apply_filters') }}</button>
        <a href="{{ route('visitors.reports') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('common.clear') }}</a>
    </div>
</form>

@if($visits->count())
<div class="card overflow-hidden !p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <th class="px-4 py-3">{{ __('common.complaint_id') }}</th>
                <th class="px-4 py-3">{{ __('common.branch') }}</th>
                <th class="px-4 py-3">{{ __('visitors.visit_type') }}</th>
                <th class="px-4 py-3">{{ __('visitors.inspector') }}</th>
                <th class="px-4 py-3">{{ __('visitors.visit_date') }}</th>
                <th class="px-4 py-3">{{ __('visitors.score') }}</th>
                <th class="px-4 py-3">{{ __('visitors.violations') }}</th>
                <th class="px-4 py-3">{{ __('common.status') }}</th>
                <th class="px-4 py-3">{{ __('visitors.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visits as $visit)
            @php
                $score = $visit->getAttribute('score');
                [$scoreHex, $scoreClasses] = $colors[$score['color']] ?? $colors['slate'];
            @endphp
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-900">#{{ $visit->id }}</td>
                <td class="px-4 py-3">{{ $visit->branch?->name ?: '—' }}</td>
                <td class="px-4 py-3">{{ $visit->visitType?->name ?: '—' }}</td>
                <td class="px-4 py-3">{{ $visit->inspector?->name ?: '—' }}</td>
                <td class="px-4 py-3">{{ $visit->visit_date?->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-bold {{ $scoreClasses }}">
                        {{ $score['percentage'] !== null ? $score['percentage'].'%' : '—' }}
                    </span>
                </td>
                <td class="px-4 py-3"><span class="{{ $visit->getAttribute('violations') ? 'font-bold text-rose-600' : 'text-slate-500' }}">{{ $visit->getAttribute('violations') }}</span></td>
                <td class="px-4 py-3"><span class="badge" style="--badge-color:#16a34a">{{ __('visitors.completed') }}</span></td>
                <td class="px-4 py-3">
                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('visitors.reports.show', $visit) }}" class="btn-secondary !px-3 !py-1.5 text-xs min-h-[36px] inline-flex items-center">{{ __('visitors.view_report') }}</a>
                        <a href="{{ route('visitors.reports.pdf', $visit) }}" class="btn-primary !px-3 !py-1.5 text-xs min-h-[36px] inline-flex items-center">{{ __('visitors.print_pdf') }}</a>
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
