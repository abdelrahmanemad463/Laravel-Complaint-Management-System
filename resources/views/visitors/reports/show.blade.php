@extends('layouts.app')
@section('content')
@php
$score = $report['score'];
$scoreColor = $score['color'];
$colors = [
    'blue'   => ['#2563eb', 'text-blue-700 bg-blue-100'],
    'green'  => ['#16a34a', 'text-emerald-700 bg-emerald-100'],
    'yellow' => ['#ca8a04', 'text-amber-700 bg-amber-100'],
    'red'    => ['#dc2626', 'text-rose-700 bg-rose-100'],
    'slate'  => ['#475569', 'text-slate-700 bg-slate-100'],
];
[$scoreHex, $scoreClasses] = $colors[$scoreColor] ?? $colors['slate'];
$severityBadge = ['critical' => '#dc2626', 'major' => '#ea580c', 'minor' => '#16a34a'];
$capaStatusColor = [
    'open' => '#2563eb', 'in_progress' => '#7c3aed', 'pending_review' => '#d97706',
    'overdue' => '#dc2626', 'closed' => '#16a34a', 'rejected' => '#475569',
];
$dueStatusColor = [
    'immediate' => '#7c3aed', 'upcoming' => '#2563eb', 'due_soon' => '#ca8a04',
    'overdue' => '#dc2626', 'completed' => '#16a34a', 'closed_late' => '#ea580c',
    'pending_review' => '#d97706', 'rejected' => '#475569',
];
$dueStatusLabel = [
    'immediate' => __('visitors.st_immediate'), 'upcoming' => __('visitors.st_upcoming'),
    'due_soon' => __('visitors.st_due_soon'), 'overdue' => __('visitors.st_overdue'),
    'completed' => __('visitors.st_completed'), 'closed_late' => __('visitors.st_closed_late'),
    'pending_review' => __('visitors.st_pending_review'), 'rejected' => __('visitors.st_rejected'),
];
$visit = $report['visit'];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4 print:hidden">
    <div>
        <a href="{{ route('visitors.reports') }}" class="back-link">← {{ __('visitors.reports') }}</a>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('visitors.reports.pdf', $visit) }}" class="btn-primary">{{ __('visitors.print_pdf') }}</a>
    </div>
</div>


<div class="mb-10 border-b-2 border-indigo-600 pb-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-slate-900">{{ __('visitors.inspection_report_heading') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $company ?? __('visitors.report_company_name') }}</p>
        </div>
        <div class="text-right">
            <span class="badge" style="--badge-color:{{ $scoreHex }}">{{ __('visitors.score_class', ['class' => __("visitors.score_class_$scoreColor")]) }}</span>
        </div>
    </div>
</div>

{{-- 2. Visit information --}}
<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('common.branch') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $visit->branch?->name ?: '—' }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.visit_date') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $visit->visit_date?->format('d/m/Y') }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.inspector') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $visit->inspector?->name ?: '—' }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.visit_type') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $visit->visitType?->name ?: '—' }}</div></div>
</div>

{{-- 3. Score summary --}}
<div class="mb-8 card">
    <h3 class="section-title mb-4">{{ __('visitors.score_summary') }}</h3>
    <div class="grid gap-4 text-center sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border p-4"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.final_score') }}</div><div class="mt-1 text-3xl font-black" style="color:{{ $scoreHex }}">{{ $score['final'] }}</div></div>
        <div class="rounded-xl border p-4"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.available_score') }}</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $score['available'] }}</div></div>
        <div class="rounded-xl border p-4"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.total_deduction') }}</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ $score['deduction'] }}</div></div>
        <div class="rounded-xl border p-4"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.percentage') }}</div><div class="mt-1 text-3xl font-black" style="color:{{ $scoreHex }}">{{ $score['percentage'] !== null ? $score['percentage'].'%' : '—' }}</div></div>
        <div class="rounded-xl border p-4"><div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.rating') }}</div><div class="mt-1 text-2xl font-bold uppercase" style="color:{{ $scoreHex }}">{{ __('visitors.score_class_'.$scoreColor) }}</div></div>
    </div>
    <div class="mt-4 grid gap-4 text-center sm:grid-cols-3">
        <div class="text-sm text-slate-600">{{ __('visitors.compliant') }}: <span class="font-bold text-emerald-600">{{ $report['counts']['ok'] }}</span></div>
        <div class="text-sm text-slate-600">{{ __('visitors.non_compliant') }}: <span class="font-bold text-rose-600">{{ $report['counts']['nc'] }}</span></div>
        <div class="text-sm text-slate-600">{{ __('visitors.not_applicable') }}: <span class="font-bold text-slate-700">{{ $report['counts']['na'] }}</span></div>
    </div>

    {{-- Violations & corrective-action due-summary cards --}}
    @php $dueSummary = $report['dueSummary']; $totalViolations = $report['counts']['nc']; $criticalViolations = $report['severity']->where('severity', 'critical')->sum('count'); @endphp
    <div class="mt-5 grid gap-4 text-center sm:grid-cols-2 lg:grid-cols-4">
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.total_violations') }}</div>
            <div class="mt-1 text-2xl font-black text-slate-900">{{ $totalViolations }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-rose-300 hover:bg-rose-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.critical_violations') }}</div>
            <div class="mt-1 text-2xl font-black {{ $criticalViolations ? 'text-rose-600' : 'text-slate-900' }}">{{ $criticalViolations }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.open_actions') }}</div>
            <div class="mt-1 text-2xl font-black text-blue-600">{{ $dueSummary['openActions'] }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.due_soon_actions') }}</div>
            <div class="mt-1 text-2xl font-black {{ $dueSummary['dueSoon'] ? 'text-amber-600' : 'text-slate-900' }}">{{ $dueSummary['dueSoon'] }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-rose-300 hover:bg-rose-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.overdue_actions') }}</div>
            <div class="mt-1 text-2xl font-black {{ $dueSummary['overdue'] ? 'text-rose-600' : 'text-slate-900' }}">{{ $dueSummary['overdue'] }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-violet-300 hover:bg-violet-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.immediate_actions') }}</div>
            <div class="mt-1 text-2xl font-black {{ $dueSummary['immediate'] ? 'text-violet-600' : 'text-slate-900' }}">{{ $dueSummary['immediate'] }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.closed_actions') }}</div>
            <div class="mt-1 text-2xl font-black text-emerald-600">{{ $dueSummary['closed'] }}</div>
        </a>
        <a href="#corrective-action-plan" class="rounded-xl border border-slate-200 p-4 transition hover:border-orange-300 hover:bg-orange-50/40 no-underline">
            <div class="text-xs font-bold uppercase text-slate-400">{{ __('visitors.closed_late_actions') }}</div>
            <div class="mt-1 text-2xl font-black {{ $dueSummary['closedLate'] ? 'text-orange-600' : 'text-slate-900' }}">{{ $dueSummary['closedLate'] }}</div>
        </a>
    </div>
</div>

{{-- 4. Non-compliant items by severity --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.nc_by_severity') }}</h3>
    @if($report['severity']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="w-full text-sm min-w-[320px] text-center">
            <thead><tr class="border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2 px-3 sm:px-4 text-center text-xs sm:text-sm">{{ __('visitors.severity') }}</th><th class="py-2 px-3 sm:px-4 text-center text-xs sm:text-sm">{{ __('common.total') }}</th><th class="py-2 px-3 sm:px-4 text-center text-xs sm:text-sm">{{ __('visitors.total_deduction') }}</th></tr></thead>
            <tbody>
                @foreach($report['severity'] as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-3 sm:px-4 text-center"><span class="badge" style="--badge-color:{{ $severityBadge[$row->severity] ?? '#475569' }}">{{ ucfirst($row->severity) }}</span></td>
                    <td class="py-2 px-3 sm:px-4 text-center font-semibold">{{ $row->count }}</td>
                    <td class="py-2 px-3 sm:px-4 text-center font-semibold text-rose-600">{{ $row->deduction }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_violations') }}</p>
    @endif
</div>

{{-- 5. Performance by section --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.performance_by_section') }}</h3>
    @if($report['sections']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm min-w-[640px] text-center">
        <thead><tr class="border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2 px-2 text-center">{{ __('visitors.section') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.total') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.compliant') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.non_compliant') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.not_applicable') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.compliance_pct') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.deduction') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['sections'] as $row)
            <tr class="border-b border-slate-100">
                <td class="py-2 px-2 text-center font-semibold">{{ $row->section }}</td>
                <td class="py-2 px-2 text-center">{{ $row->total }}</td>
                <td class="py-2 px-2 text-center text-emerald-600">{{ $row->ok }}</td>
                <td class="py-2 px-2 text-center text-rose-600">{{ $row->nc }}</td>
                <td class="py-2 px-2 text-center text-slate-500">{{ $row->na }}</td>
                <td class="py-2 px-2 text-center font-semibold">{{ $row->compliance !== null ? $row->compliance.'%' : '—' }}</td>
                <td class="py-2 px-2 text-center font-semibold text-rose-600">{{ $row->deduction }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_data') }}</p>
    @endif
</div>

{{-- 6. Root cause analysis --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.root_cause_analysis') }}</h3>
    @if($report['rootCauses']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="w-full text-sm min-w-[280px] text-center">
            <thead><tr class="border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2 px-3 sm:px-4 text-center text-xs sm:text-sm">{{ __('visitors.root_cause') }}</th><th class="py-2 px-3 sm:px-4 text-center text-xs sm:text-sm">{{ __('visitors.number_of_violations') }}</th></tr></thead>
            <tbody>
                @foreach($report['rootCauses'] as $row)
                <tr class="border-b border-slate-100"><td class="py-2 px-3 sm:px-4 text-center">{{ $row->name }}</td><td class="py-2 px-3 sm:px-4 text-center font-semibold">{{ $row->count }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_root_causes') }}</p>
    @endif
</div>

{{-- 7. Violation details --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.violation_details') }}</h3>
    @if($report['violations']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm min-w-[1080px] text-center">
        <thead><tr class="border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2 px-2 text-center">#</th><th class="py-2 px-2 text-center">{{ __('common.code') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.section') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.item') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.severity') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.root_cause') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.notes') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.evidence') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.period') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.due_status') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.capa_status') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['violations'] as $i => $entry)
            @php $item = $entry->item; $capaStatus = $entry->effectiveStatus ?? '—'; $dueStatus = $entry->dueStatus; @endphp
            <tr class="border-b border-slate-100 align-top">
                <td class="py-2 px-2 text-center">{{ $i + 1 }}</td>
                <td class="py-2 px-2 text-center"><span class="badge" style="--badge-color:#475569">{{ $item->item_code }}</span></td>
                <td class="py-2 px-2 text-center">{{ $item->section_name }}</td>
                <td class="py-2 px-2 text-center font-medium break-words">{{ $item->item_title }}</td>
                <td class="py-2 px-2 text-center"><span class="badge" style="--badge-color:{{ $severityBadge[$item->severity] ?? '#475569' }}">{{ ucfirst($item->severity) }}</span></td>
                <td class="py-2 px-2 text-center">{{ $item->rootCause?->name ?: '—' }}</td>
                <td class="py-2 px-2 text-center max-w-xs break-words">{{ $item->note ?: '—' }}</td>
                <td class="py-2 px-2 text-center">
                    <span class="inline-flex justify-center">
                    @forelse($item->photos as $photo)
                    <a href="{{ route('visitors.photos.serve', $photo) }}" data-report-lightbox data-full="{{ route('visitors.photos.serve', $photo) }}">
                        <img src="{{ route('visitors.photos.serve', $photo) }}" alt="{{ $photo->original_name }}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" loading="lazy">
                    </a>
                    @empty
                    <span class="text-slate-400">—</span>
                    @endforelse
                    </span>
                </td>
                <td class="py-2 px-2 text-center whitespace-nowrap">{{ $entry->periodLabel ?: '—' }}</td>
                <td class="py-2 px-2 text-center">
                    @if($dueStatus)
                    <span class="badge" style="--badge-color:{{ $dueStatusColor[$dueStatus] ?? '#475569' }}">{{ $dueStatusLabel[$dueStatus] ?? ucfirst($dueStatus) }}</span>
                    @if($dueStatus === 'due_soon' || $dueStatus === 'upcoming')
                    <div class="mt-0.5 text-[11px] text-slate-500">{{ $entry->dueAt?->format('d/m/Y H:i') }}</div>
                    @endif
                    @else
                    <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td class="py-2 px-2 text-center">
                    @if($capaStatus !== '—')
                    <span class="badge" style="--badge-color:{{ $capaStatusColor[$capaStatus] ?? '#475569' }}">{{ ucfirst($capaStatus) }}</span>
                    @else
                    <span class="text-slate-400">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_violations') }}</p>
    @endif
</div>

{{-- 8. Corrective action plan / CAPA --}}
<div id="corrective-action-plan" class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.corrective_action_plan') }}</h3>
    @if($report['capa']['actions']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm min-w-[1250px] text-center">
        <thead><tr class="border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2 px-2 text-center w-10">#</th><th class="py-2 px-2 text-center min-w-[190px]">{{ __('visitors.item') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.severity') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.root_cause') }}</th><th class="py-2 px-2 text-center min-w-[150px]">{{ __('visitors.immediate_action') }}</th>
            <th class="py-2 px-2 text-center min-w-[150px]">{{ __('visitors.corrective_action') }}</th><th class="py-2 px-2 text-center min-w-[150px]">{{ __('visitors.preventive_action') }}</th>
            <th class="py-2 px-2 text-center min-w-[110px]">{{ __('visitors.responsible') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.period') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.due_date') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.due_status') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.status') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['capa']['actions'] as $i => $entry)
            @php
                $action = $entry->action;
                $vi = $action->visitItem;
                $remaining = null;
                if (in_array($action->dueStatus(), ['due_soon', 'upcoming'], true) && $action->due_at) {
                    $remaining = $action->due_at->diffForHumans(now(), ['parts' => 1]);
                } elseif ($action->dueStatus() === 'overdue' && $action->due_at) {
                    $remaining = $action->due_at->diffForHumans(now(), ['parts' => 1]);
                }
            @endphp
            <tr class="border-b border-slate-100 align-top">
                <td class="py-2 px-2 text-center align-top">{{ $i + 1 }}</td>
                <td class="py-2 px-2 text-center align-top break-words whitespace-normal">{{ $vi?->item_title ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top"><span class="badge" style="--badge-color:{{ $severityBadge[$vi?->severity] ?? '#475569' }}">{{ ucfirst($vi?->severity ?? '—') }}</span></td>
                <td class="py-2 px-2 text-center align-top break-words">{{ $vi?->rootCause?->name ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top break-words whitespace-normal">{{ $action->immediate_action ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top break-words whitespace-normal">{{ $action->corrective_action ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top break-words whitespace-normal">{{ $action->preventive_action ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top break-words">{{ $action->responsible?->name ?: ($vi?->responsible ?: '—') }}</td>
                <td class="py-2 px-2 text-center align-top whitespace-nowrap">{{ $entry->periodLabel ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top whitespace-nowrap">{{ $action->due_at?->format('d/m/Y H:i') ?: '—' }}</td>
                <td class="py-2 px-2 text-center align-top">
                    <span class="badge" style="--badge-color:{{ $dueStatusColor[$entry->dueStatus] ?? '#475569' }}">{{ $dueStatusLabel[$entry->dueStatus] ?? ucfirst($entry->dueStatus) }}</span>
                    @if($remaining)
                    <div class="mt-0.5 text-[11px] text-slate-500">{{ $remaining }}</div>
                    @endif
                </td>
                <td class="py-2 px-2 text-center align-top">
                    <span class="badge" style="--badge-color:{{ $capaStatusColor[$entry->status] ?? '#475569' }}">{{ ucfirst(str_replace('_', ' ', $entry->status)) }}</span>
                    @if($entry->status === 'pending_review')
                    <div class="mt-1 text-[10px] leading-tight text-slate-500">
                        {{ __('visitors.submitted_by') }}: {{ $action->submitter?->name ?: '—' }}<br>
                        {{ $action->submitted_review_at?->format('d/m/Y H:i') }}
                    </div>
                    @endif
                    <div class="mt-1.5 flex flex-wrap items-center justify-center gap-1 print:hidden">
                        @if($entry->status === 'pending_review' && auth()->user()->can('visit.resolution.review'))
                        @can('visit.resolution.approve')
                        @if($action->submitted_by !== null && (int) $action->submitted_by !== (int) auth()->id())
                        <form method="POST" action="{{ route('visitors.violations.approve', $action) }}" class="inline" onsubmit="return confirm('{{ __('visitors.approve_confirm') }}')">
                            @csrf
                            <button type="submit" class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white hover:bg-emerald-700">{{ __('visitors.approve') }}</button>
                        </form>
                        @endif
                        @endcan
                        @can('visit.resolution.reject')
                        <form method="POST" action="{{ route('visitors.violations.reject', $action) }}" class="inline" onsubmit="return confirm('{{ __('visitors.reject_confirm') }}')">
                            @csrf
                            <input type="hidden" name="reject_reason" value="{{ __('visitors.rejected_from_report') }}">
                            <button type="submit" class="rounded-full border border-rose-300 bg-white px-2 py-0.5 text-[10px] font-bold text-rose-700 hover:bg-rose-50">{{ __('visitors.reject') }}</button>
                        </form>
                        @endcan
                        @endif
                        <a href="{{ route('visitors.violations.show', $action) }}" class="text-[10px] font-bold text-indigo-600 hover:underline">{{ __('common.view') }}</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_capa') }}</p>
    @endif
</div>

{{-- 8bis. Violation follow-ups --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.follow_up_section_title') }}</h3>
    @if($report['followUps']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm min-w-[1000px] text-center">
        <thead><tr class="border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2 px-2 text-center w-10">#</th><th class="py-2 px-2 text-center min-w-[190px]">{{ __('visitors.item') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.followed_violations') }}</th><th class="py-2 px-2 text-center min-w-[120px]">{{ __('visitors.follow_up_result') }}</th>
            <th class="py-2 px-2 text-center min-w-[110px]">{{ __('visitors.performed_by') }}</th><th class="py-2 px-2 text-center whitespace-nowrap">{{ __('visitors.follow_up_date') }}</th>
            <th class="py-2 px-2 text-center">{{ __('visitors.notes') }}</th><th class="py-2 px-2 text-center">{{ __('visitors.evidence') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['followUps'] as $i => $entry)
            @php $fu = $entry->followUp; $vi = $entry->item; @endphp
            <tr class="border-b border-slate-100 align-top">
                <td class="py-2 px-2 text-center">{{ $i + 1 }}</td>
                <td class="py-2 px-2 text-center break-words whitespace-normal">
                    @if($vi)
                    <span class="badge" style="--badge-color:#475569">{{ $vi->item_code }}</span>
                    <div class="mt-0.5 text-xs text-slate-600">{{ $vi->item_title }}</div>
                    @else
                    <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td class="py-2 px-2 text-center">
                    <span class="inline-flex flex-wrap items-center justify-center gap-1">
                    @forelse($fu->violations as $v)
                    <a href="{{ route('visitors.violations.show', $v) }}" class="badge no-underline hover:opacity-80" style="--badge-color:#d97706">#V-{{ $v->id }}</a>
                    @empty
                    <span class="text-slate-400">—</span>
                    @endforelse
                    </span>
                </td>
                <td class="py-2 px-2 text-center">
                    <span class="badge" style="--badge-color:{{ $fu->isResolved() ? '#16a34a' : '#d97706' }}">{{ $fu->isResolved() ? __('visitors.follow_up_resolved') : __('visitors.follow_up_still_open') }}</span>
                </td>
                <td class="py-2 px-2 text-center">{{ $fu->performer?->name ?: '—' }}</td>
                <td class="py-2 px-2 text-center whitespace-nowrap">{{ $fu->followed_up_at?->format('d/m/Y H:i') ?: '—' }}</td>
                <td class="py-2 px-2 text-center max-w-xs break-words">{{ $fu->follow_up_note ?: '—' }}</td>
                <td class="py-2 px-2 text-center">{{ $fu->photos->count() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_follow_ups') }}</p>
    @endif
</div>

{{-- 9. Report footer --}}
<div class="mt-10 border-t border-slate-200 pt-4 text-sm text-slate-500">
    <p><strong>{{ $company ?? __('visitors.report_company_name') }}</strong></p>
    <p>{{ __('visitors.report_generated_at') }}: {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
</div>

{{-- Lightbox --}}
<div id="report-lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true">
    <img id="report-lightbox-img" src="" alt="" class="max-h-[90vh] max-w-full rounded-lg object-contain">
    <button type="button" id="report-lightbox-close" class="absolute right-4 top-4 rounded-full bg-white/20 px-3 py-1 text-white" aria-label="{{ __('common.close') }}">✕</button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lightbox = document.getElementById('report-lightbox');
    const lightboxImg = document.getElementById('report-lightbox-img');
    const closeBtn = document.getElementById('report-lightbox-close');
    if (!lightbox || !lightboxImg) return;

    document.querySelectorAll('[data-report-lightbox]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            lightboxImg.src = link.dataset.full || link.href;
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
        });
    });

    const close = () => {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        lightboxImg.src = '';
    };
    closeBtn?.addEventListener('click', close);
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
});
</script>
@endpush
@endsection
