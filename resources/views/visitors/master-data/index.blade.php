@extends('layouts.app')
@section('content')
@php
$sevColors = [
    'critical' => 'text-rose-700 bg-rose-100',
    'major' => 'text-amber-700 bg-amber-100',
    'minor' => 'text-emerald-700 bg-emerald-100',
];
$statusBadge = [
    'pending' => ['#94a3b8'],
    'validating' => ['#3b82f6'],
    'ready' => ['#ca8a04'],
    'imported' => ['#16a34a'],
    'failed' => ['#dc2626'],
    'cancelled' => ['#475569'],
];
$statusLabel = [
    'pending' => __('visitors.master_status_pending'),
    'validating' => __('visitors.master_status_validating'),
    'ready' => __('visitors.master_status_ready'),
    'imported' => __('visitors.master_status_imported'),
    'failed' => __('visitors.master_status_failed'),
    'cancelled' => __('visitors.master_status_cancelled'),
];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('visitors.home') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
        <h1 class="page-title mt-4">{{ __('visitors.master') }}</h1>
        <p class="page-subtitle">{{ __('visitors.master_subtitle') }}</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        @can('visit.master.export')
        <form method="GET" action="{{ route('visitors.master-data.download') }}" class="inline-flex gap-2 w-full sm:w-auto">
            <input type="hidden" name="visit_type_id" value="{{ $filters['visit_type_id'] ?? '' }}">
            <input type="hidden" name="section_id" value="{{ $filters['section_id'] ?? '' }}">
            <button class="btn-secondary min-h-[44px] w-full sm:w-auto">{{ __('visitors.master_download_current') }}</button>
        </form>
        @endcan
        @can('visit.master.view')
        <a href="{{ route('visitors.master-data.template') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('visitors.master_download_template') }}</a>
        <a href="{{ route('visitors.master-data.template-example') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('visitors.master_download_template_example') }}</a>
        @endcan
    </div>
</div>

{{-- Statistics --}}
<div class="mb-6 grid gap-3 sm:gap-4 grid-cols-1 xs:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_total_items') }}</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['total'] }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_daily_items') }}</div><div class="mt-1 text-2xl font-bold text-indigo-700">{{ $stats['daily'] }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_monthly_items') }}</div><div class="mt-1 text-2xl font-bold text-emerald-700">{{ $stats['monthly'] }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_safety_items') }}</div><div class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['safety'] }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_total_sections') }}</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['sections'] }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.master_last_import') }}</div><div class="mt-1 text-sm font-bold text-slate-900">{{ $stats['last_import'] ? \Illuminate\Support\Carbon::parse($stats['last_import'])->format('d/m/Y H:i') : '—' }}</div></div>
</div>

{{-- Upload --}}
@can('visit.master.import')
<div class="card mb-6 p-4 sm:p-6">
    <h2 class="mb-1 text-lg font-bold text-slate-900">{{ __('visitors.master_upload_excel') }}</h2>
    <p class="mb-4 text-sm text-slate-500">{{ __('visitors.master_upload_help') }}</p>
    <form method="POST" action="{{ route('visitors.master-data.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <input type="file" name="file" accept=".xlsx,.xls" required class="form-input flex-1 min-w-0">
            <button type="submit" class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('visitors.master_upload_submit') }}</button>
        </div>
        @error('file')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </form>
</div>
@endcan

{{-- Filters + current data --}}
<h2 class="mb-3 text-lg font-bold text-slate-900">{{ __('visitors.master_current_data') }}</h2>
<form method="GET" action="{{ route('visitors.master-data') }}" class="card mb-6 p-4 sm:p-6">
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label class="form-label">{{ __('visitors.master_filter_type') }}</label>
            <select name="visit_type_id" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($types as $type)
                <option value="{{ $type->id }}" @selected(($filters['visit_type_id'] ?? null) == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.master_filter_section') }}</label>
            <select name="section_id" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($sections as $section)
                <option value="{{ $section->id }}" @selected(($filters['section_id'] ?? null) == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.master_filter_severity') }}</label>
            <select name="severity" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach(['critical','major','minor'] as $sev)
                <option value="{{ $sev }}" @selected(($filters['severity'] ?? null) === $sev)>{{ ucfirst($sev) }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="form-label">{{ __('visitors.master_search') }}</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input" placeholder="{{ __('visitors.master_search') }}">
        </div>
    </div>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <button type="submit" class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('visitors.master_filter') }}</button>
        <a href="{{ route('visitors.master-data') }}" class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto">{{ __('visitors.master_clear') }}</a>
    </div>
</form>

@if($items->count())
<div class="card overflow-hidden !p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <th class="px-4 py-3">{{ __('visitors.master_code') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_inspection_type') }}</th>
                <th class="px-4 py-3">{{ __('visitors.section') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_note') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_severity') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_deduction_score') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_is_active') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_last_updated') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->code }}</td>
                <td class="px-4 py-3">{{ $item->visitType?->name ?: '—' }}</td>
                <td class="px-4 py-3">{{ $item->section?->name ?: '—' }}</td>
                <td class="px-4 py-3">{{ $item->title }}</td>
                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $sevColors[$item->severity] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($item->severity) }}</span></td>
                <td class="px-4 py-3 font-semibold">{{ $item->deduction_score }}</td>
                <td class="px-4 py-3"><span class="badge" style="--badge-color:{{ $item->is_active ? '#16a34a' : '#94a3b8' }}">{{ $item->is_active ? __('visitors.master_active') : __('visitors.master_inactive') }}</span></td>
                <td class="px-4 py-3 text-slate-500">{{ $item->updated_at?->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@else
<div class="card p-4 sm:p-6 text-center text-slate-500">{{ __('visitors.master_no_items') }}</div>
@endif

{{-- Import history --}}
<h2 class="mb-3 mt-10 text-lg font-bold text-slate-900">{{ __('visitors.master_import_history') }}</h2>
@if($history->count())
<div class="card overflow-hidden !p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <th class="px-4 py-3">{{ __('visitors.master_import_file') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_by') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_date') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_size') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_rows') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_created') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_updated') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_failed') }}</th>
                <th class="px-4 py-3">{{ __('visitors.master_import_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $h)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-900">{{ $h->file_name }}</td>
                <td class="px-4 py-3">{{ $h->user?->name ?: '—' }}</td>
                <td class="px-4 py-3">{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3">{{ number_format($h->file_size / 1048576, 2) }} MB</td>
                <td class="px-4 py-3">{{ $h->total_rows }}</td>
                <td class="px-4 py-3 text-emerald-700">{{ $h->created_records }}</td>
                <td class="px-4 py-3 text-amber-700">{{ $h->updated_records }}</td>
                <td class="px-4 py-3 text-rose-600">{{ $h->invalid_rows }}</td>
                <td class="px-4 py-3"><span class="badge" style="--badge-color:{{ ($statusBadge[$h->status] ?? ['#94a3b8'])[0] }}">@if($h->isReady())<a class="underline" href="{{ route('visitors.master-data.import.preview', $h) }}">{{ $statusLabel[$h->status] }}</a>@else{{ $statusLabel[$h->status] }}@endif</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $history->links('pagination::tailwind') }}</div>
@else
<div class="card p-4 sm:p-6 text-center text-slate-500">{{ __('visitors.master_no_history') }}</div>
@endif
@endsection
