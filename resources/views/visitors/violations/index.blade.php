@extends('layouts.app')
@section('content')
@php
$statusColor = [
    'open' => '#2563eb', 'in_progress' => '#7c3aed', 'pending_review' => '#d97706',
    'overdue' => '#dc2626', 'closed' => '#16a34a', 'rejected' => '#475569',
];
$dueStatusColor = [
    'immediate' => '#7c3aed', 'upcoming' => '#2563eb', 'due_soon' => '#ca8a04',
    'overdue' => '#dc2626', 'completed' => '#16a34a', 'closed_late' => '#ea580c',
    'rejected' => '#475569', 'pending_review' => '#d97706',
];
$severityBadge = ['critical' => '#dc2626', 'major' => '#ea580c', 'minor' => '#16a34a'];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('visitors.home') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
        <h1 class="page-title mt-4">{{ __('visitors.follow_up_report') }}</h1>
        <p class="page-subtitle">{{ __('visitors.follow_up_subtitle') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @can('visit.create')<a href="{{ route('visitors.create') }}" class="btn-primary">{{ __('visitors.new_visit') }}</a>@endcan
    </div>
</div>

<form method="GET" action="{{ route('visitors.violations') }}" class="card mb-6">
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="form-label">{{ __('common.branch') }}</label>
            <select name="branch_id" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->localized_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.status') }}</label>
            <select name="status" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                @foreach(['open','in_progress','pending_review','closed','rejected'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('visitors.due_status') }}</label>
            <select name="due_status" class="form-input">
                <option value="">{{ __('common.all') }}</option>
                <option value="immediate" @selected(request('due_status') === 'immediate')>{{ __('visitors.st_immediate') }}</option>
                <option value="overdue" @selected(request('due_status') === 'overdue')>{{ __('visitors.st_overdue') }}</option>
                <option value="closed" @selected(request('due_status') === 'closed')>{{ __('visitors.st_completed') }}</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary w-full">{{ __('common.filter') }}</button>
        </div>
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[1080px]">
        <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">{{ __('visitors.violation') }}</th>
            <th class="px-4 py-3">{{ __('common.branch') }}</th>
            <th class="px-4 py-3">{{ __('visitors.inspector') }}</th>
            <th class="px-4 py-3">{{ __('visitors.severity') }}</th>
            <th class="px-4 py-3">{{ __('visitors.due_date') }}</th>
            <th class="px-4 py-3">{{ __('visitors.due_status') }}</th>
            <th class="px-4 py-3">{{ __('visitors.status') }}</th>
            <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
        </tr></thead>
        <tbody>
        @forelse($violations as $v)
        @php
            $status = $v->effectiveStatus();
            $dueStatus = $v->dueStatus();
        @endphp
        <tr class="border-b border-slate-100 hover:bg-slate-50">
            <td class="px-4 py-3 font-bold text-slate-900">#V-{{ $v->id }}</td>
            <td class="px-4 py-3">
                <div class="font-semibold text-slate-900">{{ $v->visitItem?->item_code }}</div>
                <div class="text-xs text-slate-500 max-w-xs truncate">{{ $v->visitItem?->item_title }}</div>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ $v->visit?->branch?->localized_name ?: '—' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ $v->visit?->inspector?->name ?: '—' }}</td>
            <td class="px-4 py-3"><span class="badge" style="--badge-color:{{ $severityBadge[$v->visitItem?->severity] ?? '#475569' }}">{{ ucfirst($v->visitItem?->severity ?? '—') }}</span></td>
            <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ $v->due_at?->format('d/m/Y H:i') ?: '—' }}</td>
            <td class="px-4 py-3"><span class="badge" style="--badge-color:{{ $dueStatusColor[$dueStatus] ?? '#475569' }}">{{ ucfirst(str_replace('_', ' ', $dueStatus)) }}</span></td>
            <td class="px-4 py-3">
                <span class="badge" style="--badge-color:{{ $statusColor[$status] ?? '#475569' }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                @if($v->status === 'pending_review')
                <div class="mt-1 text-[10px] leading-tight text-slate-500">
                    {{ __('visitors.submitted_by') }}: {{ $v->submitter?->name ?: '—' }}<br>
                    {{ $v->submitted_review_at?->format('d/m/Y H:i') }}
                </div>
                @endif
            </td>
            <td class="px-4 py-3 text-right">
                <a href="{{ route('visitors.violations.show', $v) }}" class="text-indigo-600 hover:underline text-xs font-bold">{{ __('common.view') }}</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">{{ __('visitors.no_violations') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @if($violations->hasPages())
    <div class="border-t border-slate-100 px-4 py-3">{{ $violations->links() }}</div>
    @endif
</div>
@endsection