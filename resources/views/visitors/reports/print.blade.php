<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('visitors.inspection_report') }} #{{ $report['visit']->id }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.45; margin: 0; padding: 24px; }
    h1 { font-size: 20px; margin: 0; }
    h2 { font-size: 13px; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #4f46e5; color: #1e1b4b; }
    .muted { color: #64748b; }
    .header { border-bottom: 3px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
    .header td { vertical-align: middle; }
    .company { color: #4f46e5; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #eef2ff; text-align: center; padding: 5px 6px; font-size: 10px; text-transform: uppercase; }
    td { border: 1px solid #e2e8f0; padding: 5px 6px; vertical-align: top; text-align: center; word-wrap: break-word; overflow-wrap: break-word; }
    .info td { border: none; }
    .score { text-align: center; border: 1px solid #e2e8f0; padding: 8px; }
    .score .value { font-size: 22px; font-weight: 800; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; font-weight: 700; }
    .nc { color: #dc2626; font-weight: 700; }
    .ok { color: #16a34a; font-weight: 700; }
    .img { width: 52px; height: 52px; object-fit: cover; border: 1px solid #e2e8f0; }
    .footer { margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 8px; color: #64748b; font-size: 10px; }
    .page-footer { text-align: center; color: #94a3b8; font-size: 9px; }
    .grid-5 { width: 100%; }
    .grid-5 td { border: none; }
</style>
</head>
<body>
@php
$score = $report['score'];
$visit = $report['visit'];
$colors = ['blue' => '#2563eb', 'green' => '#16a34a', 'yellow' => '#ca8a04', 'red' => '#dc2626', 'slate' => '#475569'];
$sev = ['critical' => '#dc2626', 'major' => '#ea580c', 'minor' => '#16a34a'];
$capaCol = ['open' => '#2563eb', 'in_progress' => '#7c3aed', 'overdue' => '#dc2626', 'closed' => '#16a34a', 'rejected' => '#475569'];
$hex = $colors[$score['color']] ?? '#475569';
@endphp

{{-- 1. Report header --}}
<table class="header"><tr>
    <td>
        <h1>{{ __('visitors.inspection_report_heading') }}</h1>
        <div class="company">{{ $company }}</div>
    </td>
    <td style="text-align:right">
        <div class="badge" style="background:{{ $hex }}22; color:{{ $hex }}">{{ __('visitors.score_class', ['class' => __("visitors.score_class_".$score['color'])]) }}</div>
    </td>
</tr></table>

{{-- 2. Visit information --}}
<h2>{{ __('visitors.visit_information') }}</h2>
<table class="info">
    <tr>
        <td><b>{{ __('common.branch') }}:</b> {{ $visit->branch?->name ?: '—' }}</td>
        <td><b>{{ __('visitors.visit_date') }}:</b> {{ $visit->visit_date?->format('d/m/Y') }}</td>
        <td><b>{{ __('visitors.inspector') }}:</b> {{ $visit->inspector?->name ?: '—' }}</td>
        <td><b>{{ __('visitors.visit_type') }}:</b> {{ $visit->visitType?->name ?: '—' }}</td>
    </tr>
</table>

{{-- 3. Score summary --}}
<h2>{{ __('visitors.score_summary') }}</h2>
<table class="grid-5">
    <tr>
        <td class="score"><div class="muted">{{ __('visitors.final_score') }}</div><div class="value" style="color:{{ $hex }}">{{ $score['final'] }}</div></td>
        <td class="score"><div class="muted">{{ __('visitors.available_score') }}</div><div class="value">{{ $score['available'] }}</div></td>
        <td class="score"><div class="muted">{{ __('visitors.total_deduction') }}</div><div class="value nc">{{ $score['deduction'] }}</div></td>
        <td class="score"><div class="muted">{{ __('visitors.percentage') }}</div><div class="value" style="color:{{ $hex }}">{{ $score['percentage'] !== null ? $score['percentage'].'%' : '—' }}</div></td>
        <td class="score"><div class="muted">{{ __('visitors.rating') }}</div><div class="value" style="color:{{ $hex }}; font-size:13px">{{ __('visitors.score_class_'.$score['color']) }}</div></td>
    </tr>
</table>
<p style="margin:8px 0 0">
    {{ __('visitors.compliant') }}: <b class="ok">{{ $report['counts']['ok'] }}</b> ·
    {{ __('visitors.non_compliant') }}: <b class="nc">{{ $report['counts']['nc'] }}</b> ·
    {{ __('visitors.not_applicable') }}: <b>{{ $report['counts']['na'] }}</b>
</p>

{{-- 4. Non-compliant by severity --}}
<h2>{{ __('visitors.nc_by_severity') }}</h2>
@if($report['severity']->count())
<table>
    <thead><tr><th>{{ __('visitors.severity') }}</th><th>{{ __('common.total') }}</th><th>{{ __('visitors.total_deduction') }}</th></tr></thead>
    <tbody>
    @foreach($report['severity'] as $row)
    <tr><td><span class="badge" style="background:{{ ($sev[$row->severity] ?? '#475569') }}22; color:{{ $sev[$row->severity] ?? '#475569' }}">{{ ucfirst($row->severity) }}</span></td><td>{{ $row->count }}</td><td class="nc">{{ $row->deduction }}</td></tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ __('visitors.no_violations') }}</p>
@endif

{{-- 5. Performance by section --}}
<h2>{{ __('visitors.performance_by_section') }}</h2>
<table>
    <thead><tr><th>{{ __('visitors.section') }}</th><th>{{ __('visitors.total') }}</th><th>{{ __('visitors.compliant') }}</th><th>{{ __('visitors.non_compliant') }}</th><th>{{ __('visitors.not_applicable') }}</th><th>{{ __('visitors.compliance_pct') }}</th><th>{{ __('visitors.deduction') }}</th></tr></thead>
    <tbody>
    @foreach($report['sections'] as $row)
    <tr><td><b>{{ $row->section }}</b></td><td>{{ $row->total }}</td><td class="ok">{{ $row->ok }}</td><td class="nc">{{ $row->nc }}</td><td>{{ $row->na }}</td><td>{{ $row->compliance !== null ? $row->compliance.'%' : '—' }}</td><td class="nc">{{ $row->deduction }}</td></tr>
    @endforeach
    </tbody>
</table>

{{-- 6. Root cause analysis --}}
<h2>{{ __('visitors.root_cause_analysis') }}</h2>
@if($report['rootCauses']->count())
<table>
    <thead><tr><th>{{ __('visitors.root_cause') }}</th><th>{{ __('visitors.number_of_violations') }}</th></tr></thead>
    <tbody>
    @foreach($report['rootCauses'] as $row)
    <tr><td>{{ $row->name }}</td><td>{{ $row->count }}</td></tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ __('visitors.no_root_causes') }}</p>
@endif

{{-- 7. Violation details --}}
<h2>{{ __('visitors.violation_details') }}</h2>
@if($report['violations']->count())
<table>
    <thead><tr><th>#</th><th>{{ __('common.code') }}</th><th>{{ __('visitors.section') }}</th><th>{{ __('visitors.item') }}</th><th>{{ __('visitors.severity') }}</th><th>{{ __('visitors.root_cause') }}</th><th>{{ __('visitors.notes') }}</th><th>{{ __('visitors.evidence') }}</th><th>{{ __('visitors.capa_status') }}</th></tr></thead>
    <tbody>
    @foreach($report['violations'] as $i => $item)
    @php
        $capa = $item->capaAction ? $item->capaAction->effectiveStatus() : null;
        $photoPath = isset($item->photos) && $item->photos->count() ? ($photos[$item->photos->first()->id] ?? null) : null;
    @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td><b>{{ $item->item_code }}</b></td>
        <td>{{ $item->section_name }}</td>
        <td>{{ $item->item_title }}</td>
        <td><span class="badge" style="background:{{ ($sev[$item->severity] ?? '#475569') }}22; color:{{ $sev[$item->severity] ?? '#475569' }}">{{ ucfirst($item->severity) }}</span></td>
        <td>{{ $item->rootCause?->name ?: '—' }}</td>
        <td>{{ $item->note ?: '—' }}</td>
        <td>@if($photoPath)<img class="img" src="{{ $photoPath }}" alt="">@else — @endif</td>
        <td>@if($capa)<span class="badge" style="background:{{ ($capaCol[$capa] ?? '#475569') }}22; color:{{ $capaCol[$capa] ?? '#475569' }}">{{ ucfirst($capa) }}</span>@else — @endif</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ __('visitors.no_violations') }}</p>
@endif

{{-- 8. CAPA / action plan --}}
<h2>{{ __('visitors.corrective_action_plan') }}</h2>
@if($report['capa']['actions']->count())
<table style="font-size:9px">
    <thead><tr><th>#</th><th>{{ __('visitors.item') }}</th><th>{{ __('visitors.severity') }}</th><th>{{ __('visitors.root_cause') }}</th><th>{{ __('visitors.immediate_action') }}</th><th>{{ __('visitors.corrective_action') }}</th><th>{{ __('visitors.preventive_action') }}</th><th>{{ __('visitors.responsible') }}</th><th>{{ __('visitors.due_date') }}</th><th>{{ __('visitors.status') }}</th></tr></thead>
    <tbody>
    @foreach($report['capa']['actions'] as $i => $entry)
    @php
        $a = $entry->action;
        $vi = $a->visitItem;
    @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $vi?->item_title ?: '—' }}</td>
        <td><span class="badge" style="background:{{ ($sev[$vi?->severity] ?? '#475569') }}22; color:{{ $sev[$vi?->severity] ?? '#475569' }}">{{ ucfirst($vi?->severity ?? '—') }}</span></td>
        <td>{{ $vi?->rootCause?->name ?: '—' }}</td>
        <td>{{ $a->immediate_action ?: '—' }}</td>
        <td>{{ $a->corrective_action ?: '—' }}</td>
        <td>{{ $a->preventive_action ?: '—' }}</td>
        <td>{{ $a->responsible?->name ?: ($vi?->responsible ?: '—') }}</td>
        <td>{{ $a->due_date?->format('d/m/Y') ?: '—' }}</td>
        <td><span class="badge" style="background:{{ ($capaCol[$entry->status] ?? '#475569') }}22; color:{{ $capaCol[$entry->status] ?? '#475569' }}">{{ ucfirst($entry->status) }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ __('visitors.no_capa') }}</p>
@endif

{{-- 9. Report footer --}}
<div class="footer">
    <strong>{{ $company }}</strong><br>
    {{ __('visitors.report_generated_at') }}: {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
</div>
</body>
</html>
