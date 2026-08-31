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
    'open' => '#2563eb', 'in_progress' => '#7c3aed', 'overdue' => '#dc2626',
    'closed' => '#16a34a', 'rejected' => '#475569',
];
$visit = $report['visit'];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4 print:hidden">
    <div>
        <a href="{{ route('visitors.reports') }}" class="back-link">← {{ __('visitors.reports') }}</a>
        <h1 class="page-title mt-4">{{ __('visitors.inspection_report') }}</h1>
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
</div>

{{-- 4. Non-compliant items by severity --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.nc_by_severity') }}</h3>
    @if($report['severity']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="w-full text-sm min-w-[320px]">
            <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2 px-3 sm:px-4 text-xs sm:text-sm">{{ __('visitors.severity') }}</th><th class="py-2 px-3 sm:px-4 text-xs sm:text-sm">{{ __('common.total') }}</th><th class="py-2 px-3 sm:px-4 text-xs sm:text-sm">{{ __('visitors.total_deduction') }}</th></tr></thead>
            <tbody>
                @foreach($report['severity'] as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-3 sm:px-4"><span class="badge" style="--badge-color:{{ $severityBadge[$row->severity] ?? '#475569' }}">{{ ucfirst($row->severity) }}</span></td>
                    <td class="py-2 px-3 sm:px-4 font-semibold">{{ $row->count }}</td>
                    <td class="py-2 px-3 sm:px-4 font-semibold text-rose-600">{{ $row->deduction }}</td>
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
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2">{{ __('visitors.section') }}</th><th class="py-2">{{ __('visitors.total') }}</th>
            <th class="py-2">{{ __('visitors.compliant') }}</th><th class="py-2">{{ __('visitors.non_compliant') }}</th>
            <th class="py-2">{{ __('visitors.not_applicable') }}</th><th class="py-2">{{ __('visitors.compliance_pct') }}</th>
            <th class="py-2">{{ __('visitors.deduction') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['sections'] as $row)
            <tr class="border-b border-slate-100">
                <td class="py-2 font-semibold">{{ $row->section }}</td>
                <td class="py-2">{{ $row->total }}</td>
                <td class="py-2 text-emerald-600">{{ $row->ok }}</td>
                <td class="py-2 text-rose-600">{{ $row->nc }}</td>
                <td class="py-2 text-slate-500">{{ $row->na }}</td>
                <td class="py-2 font-semibold">{{ $row->compliance !== null ? $row->compliance.'%' : '—' }}</td>
                <td class="py-2 font-semibold text-rose-600">{{ $row->deduction }}</td>
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
        <table class="w-full text-sm min-w-[280px]">
            <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><th class="py-2 px-3 sm:px-4 text-xs sm:text-sm">{{ __('visitors.root_cause') }}</th><th class="py-2 px-3 sm:px-4 text-xs sm:text-sm">{{ __('visitors.number_of_violations') }}</th></tr></thead>
            <tbody>
                @foreach($report['rootCauses'] as $row)
                <tr class="border-b border-slate-100"><td class="py-2 px-3 sm:px-4">{{ $row->name }}</td><td class="py-2 px-3 sm:px-4 font-semibold">{{ $row->count }}</td></tr>
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
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2">#</th><th class="py-2">{{ __('common.code') }}</th><th class="py-2">{{ __('visitors.section') }}</th>
            <th class="py-2">{{ __('visitors.item') }}</th><th class="py-2">{{ __('visitors.severity') }}</th>
            <th class="py-2">{{ __('visitors.root_cause') }}</th><th class="py-2">{{ __('visitors.notes') }}</th>
            <th class="py-2">{{ __('visitors.evidence') }}</th><th class="py-2">{{ __('visitors.capa_status') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['violations'] as $i => $item)
            @php $capaStatus = $item->capaAction ? $item->capaAction->effectiveStatus() : '—'; @endphp
            <tr class="border-b border-slate-100 align-top">
                <td class="py-2">{{ $i + 1 }}</td>
                <td class="py-2"><span class="badge" style="--badge-color:#475569">{{ $item->item_code }}</span></td>
                <td class="py-2">{{ $item->section_name }}</td>
                <td class="py-2 font-medium">{{ $item->item_title }}</td>
                <td class="py-2"><span class="badge" style="--badge-color:{{ $severityBadge[$item->severity] ?? '#475569' }}">{{ ucfirst($item->severity) }}</span></td>
                <td class="py-2">{{ $item->rootCause?->name ?: '—' }}</td>
                <td class="py-2 max-w-xs">{{ $item->note ?: '—' }}</td>
                <td class="py-2">
                    @forelse($item->photos as $photo)
                    <a href="{{ route('visitors.photos.serve', $photo) }}" data-report-lightbox data-full="{{ route('visitors.photos.serve', $photo) }}">
                        <img src="{{ route('visitors.photos.serve', $photo) }}" alt="{{ $photo->original_name }}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" loading="lazy">
                    </a>
                    @empty
                    <span class="text-slate-400">—</span>
                    @endforelse
                </td>
                <td class="py-2">
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
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.corrective_action_plan') }}</h3>
    @if($report['capa']['actions']->count())
    <div class="overflow-x-auto -mx-3 sm:mx-0">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-2">#</th><th class="py-2">{{ __('visitors.item') }}</th><th class="py-2">{{ __('visitors.severity') }}</th>
            <th class="py-2">{{ __('visitors.root_cause') }}</th><th class="py-2">{{ __('visitors.immediate_action') }}</th>
            <th class="py-2">{{ __('visitors.corrective_action') }}</th><th class="py-2">{{ __('visitors.preventive_action') }}</th>
            <th class="py-2">{{ __('visitors.responsible') }}</th><th class="py-2">{{ __('visitors.due_date') }}</th>
            <th class="py-2">{{ __('visitors.status') }}</th>
        </tr></thead>
        <tbody>
            @foreach($report['capa']['actions'] as $i => $entry)
            @php
                $action = $entry->action;
                $vi = $action->visitItem;
            @endphp
            <tr class="border-b border-slate-100 align-top">
                <td class="py-2">{{ $i + 1 }}</td>
                <td class="py-2">{{ $vi?->item_title ?: '—' }}</td>
                <td class="py-2"><span class="badge" style="--badge-color:{{ $severityBadge[$vi?->severity] ?? '#475569' }}">{{ ucfirst($vi?->severity ?? '—') }}</span></td>
                <td class="py-2">{{ $vi?->rootCause?->name ?: '—' }}</td>
                <td class="py-2 max-w-xs">{{ $action->immediate_action ?: '—' }}</td>
                <td class="py-2 max-w-xs">{{ $action->corrective_action ?: '—' }}</td>
                <td class="py-2 max-w-xs">{{ $action->preventive_action ?: '—' }}</td>
                <td class="py-2">{{ $action->responsible?->name ?: ($vi?->responsible ?: '—') }}</td>
                <td class="py-2">{{ $action->due_date?->format('d/m/Y') ?: '—' }}</td>
                <td class="py-2"><span class="badge" style="--badge-color:{{ $capaStatusColor[$entry->status] ?? '#475569' }}">{{ ucfirst($entry->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <p class="text-slate-500">{{ __('visitors.no_capa') }}</p>
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
