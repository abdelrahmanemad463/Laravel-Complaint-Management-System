<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('visitors.inspection_report') }} #{{ $report['visit']->id }}</title>
<style>
    @font-face {
        font-family: 'Amiri';
        src: url({{ str_replace('\\', '/', storage_path('fonts/Amiri-Regular.ttf')) }}) format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    @font-face {
        font-family: 'Amiri';
        src: url({{ str_replace('\\', '/', storage_path('fonts/Amiri-Bold.ttf')) }}) format('truetype');
        font-weight: bold;
        font-style: normal;
    }
    * { box-sizing: border-box; }
    body { font-family: Amiri, 'DejaVu Sans', sans-serif; color: #0f172a; font-size: 12px; line-height: 1.55; margin: 0; padding: 24px; }
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
$dueCol = ['immediate' => '#7c3aed', 'upcoming' => '#2563eb', 'due_soon' => '#ca8a04', 'overdue' => '#dc2626', 'completed' => '#16a34a', 'closed_late' => '#ea580c', 'pending_review' => '#d97706', 'rejected' => '#475569'];
$dueLbl = ['immediate' => __('visitors.st_immediate'), 'upcoming' => __('visitors.st_upcoming'), 'due_soon' => __('visitors.st_due_soon'), 'overdue' => __('visitors.st_overdue'), 'completed' => __('visitors.st_completed'), 'closed_late' => __('visitors.st_closed_late'), 'pending_review' => __('visitors.st_pending_review'), 'rejected' => __('visitors.st_rejected')];
$hex = $colors[$score['color']] ?? '#475569';
$sh = fn($s) => pdf_ar($s);
@endphp

{{-- 1. Report header --}}
<table class="header"><tr>
    <td>
        <h1>{{ $sh(__('visitors.inspection_report_heading')) }}</h1>
        <div class="company">{{ $sh($company) }}</div>
    </td>
    <td style="text-align:right">
        <div class="badge" style="background:{{ $hex }}22; color:{{ $hex }}">{{ $sh(__('visitors.score_class', ['class' => __("visitors.score_class_".$score['color'])])) }}</div>
    </td>
</tr></table>

{{-- 2. Visit information --}}
<h2>{{ $sh(__('visitors.visit_information')) }}</h2>
<table class="info">
    <tr>
        <td><b>{{ $sh(__('common.branch')) }}:</b> {{ $sh($visit->branch?->name ?: '—') }}</td>
        <td><b>{{ $sh(__('visitors.visit_date')) }}:</b> {{ $visit->visit_date?->format('d/m/Y') }}</td>
        <td><b>{{ $sh(__('visitors.inspector')) }}:</b> {{ $sh($visit->inspector?->name ?: '—') }}</td>
        <td><b>{{ $sh(__('visitors.visit_type')) }}:</b> {{ $sh($visit->visitType?->name ?: '—') }}</td>
    </tr>
</table>

{{-- 3. Score summary --}}
<h2>{{ $sh(__('visitors.score_summary')) }}</h2>
<table class="grid-5">
    <tr>
        <td class="score"><div class="muted">{{ $sh(__('visitors.final_score')) }}</div><div class="value" style="color:{{ $hex }}">{{ $score['final'] }}</div></td>
        <td class="score"><div class="muted">{{ $sh(__('visitors.available_score')) }}</div><div class="value">{{ $score['available'] }}</div></td>
        <td class="score"><div class="muted">{{ $sh(__('visitors.total_deduction')) }}</div><div class="value nc">{{ $score['deduction'] }}</div></td>
        <td class="score"><div class="muted">{{ $sh(__('visitors.percentage')) }}</div><div class="value" style="color:{{ $hex }}">{{ $score['percentage'] !== null ? $score['percentage'].'%' : '—' }}</div></td>
        <td class="score"><div class="muted">{{ $sh(__('visitors.rating')) }}</div><div class="value" style="color:{{ $hex }}; font-size:13px">{{ $sh(__('visitors.score_class_'.$score['color'])) }}</div></td>
    </tr>
</table>
<p style="margin:8px 0 0">
    {{ $sh(__('visitors.compliant')) }}: <b class="ok">{{ $report['counts']['ok'] }}</b> ·
    {{ $sh(__('visitors.non_compliant')) }}: <b class="nc">{{ $report['counts']['nc'] }}</b> ·
    {{ $sh(__('visitors.not_applicable')) }}: <b>{{ $report['counts']['na'] }}</b>
</p>

{{-- 4. Non-compliant by severity --}}
<h2>{{ $sh(__('visitors.nc_by_severity')) }}</h2>
@if($report['severity']->count())
<table>
    <thead><tr><th>{{ $sh(__('visitors.severity')) }}</th><th>{{ $sh(__('common.total')) }}</th><th>{{ $sh(__('visitors.total_deduction')) }}</th></tr></thead>
    <tbody>
    @foreach($report['severity'] as $row)
    <tr><td><span class="badge" style="background:{{ ($sev[$row->severity] ?? '#475569') }}22; color:{{ $sev[$row->severity] ?? '#475569' }}">{{ $sh(ucfirst($row->severity)) }}</span></td><td>{{ $row->count }}</td><td class="nc">{{ $row->deduction }}</td></tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ $sh(__('visitors.no_violations')) }}</p>
@endif

{{-- 5. Performance by section --}}
<h2>{{ $sh(__('visitors.performance_by_section')) }}</h2>
<table>
    <thead><tr><th>{{ $sh(__('visitors.section')) }}</th><th>{{ $sh(__('visitors.total')) }}</th><th>{{ $sh(__('visitors.compliant')) }}</th><th>{{ $sh(__('visitors.non_compliant')) }}</th><th>{{ $sh(__('visitors.not_applicable')) }}</th><th>{{ $sh(__('visitors.compliance_pct')) }}</th><th>{{ $sh(__('visitors.deduction')) }}</th></tr></thead>
    <tbody>
    @foreach($report['sections'] as $row)
    <tr><td><b>{{ $sh($row->section) }}</b></td><td>{{ $row->total }}</td><td class="ok">{{ $row->ok }}</td><td class="nc">{{ $row->nc }}</td><td>{{ $row->na }}</td><td>{{ $row->compliance !== null ? $row->compliance.'%' : '—' }}</td><td class="nc">{{ $row->deduction }}</td></tr>
    @endforeach
    </tbody>
</table>

{{-- 6. Root cause analysis --}}
<h2>{{ $sh(__('visitors.root_cause_analysis')) }}</h2>
@if($report['rootCauses']->count())
<table>
    <thead><tr><th>{{ $sh(__('visitors.root_cause')) }}</th><th>{{ $sh(__('visitors.number_of_violations')) }}</th></tr></thead>
    <tbody>
    @foreach($report['rootCauses'] as $row)
    <tr><td>{{ $sh($row->name) }}</td><td>{{ $row->count }}</td></tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ $sh(__('visitors.no_root_causes')) }}</p>
@endif

{{-- 7. Violation details --}}
<h2>{{ $sh(__('visitors.violation_details')) }}</h2>
@if($report['violations']->count())
<table>
    <thead><tr><th>#</th><th>{{ $sh(__('common.code')) }}</th><th>{{ $sh(__('visitors.section')) }}</th><th>{{ $sh(__('visitors.item')) }}</th><th>{{ $sh(__('visitors.severity')) }}</th><th>{{ $sh(__('visitors.root_cause')) }}</th><th>{{ $sh(__('visitors.notes')) }}</th><th>{{ $sh(__('visitors.evidence')) }}</th><th>{{ $sh(__('visitors.period')) }}</th><th>{{ $sh(__('visitors.due_status')) }}</th><th>{{ $sh(__('visitors.capa_status')) }}</th></tr></thead>
    <tbody>
    @foreach($report['violations'] as $i => $entry)
    @php
        $item = $entry->item;
        $capa = $entry->effectiveStatus;
        $dueStatus = $entry->dueStatus;
        $photoPath = isset($item->photos) && $item->photos->count() ? ($photos[$item->photos->first()->id] ?? null) : null;
    @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td><b>{{ $item->item_code }}</b></td>
        <td>{{ $sh($item->section_name) }}</td>
        <td>{{ $sh($item->item_title) }}</td>
        <td><span class="badge" style="background:{{ ($sev[$item->severity] ?? '#475569') }}22; color:{{ $sev[$item->severity] ?? '#475569' }}">{{ $sh(ucfirst($item->severity)) }}</span></td>
        <td>{{ $sh($item->rootCause?->name ?: '—') }}</td>
        <td>{{ $sh($item->note ?: '—') }}</td>
        <td>@if($photoPath)<img class="img" src="{{ $photoPath }}" alt="">@else — @endif</td>
        <td>{{ $sh($entry->periodLabel ?: '—') }}</td>
        <td>@if($dueStatus)<span class="badge" style="background:{{ ($dueCol[$dueStatus] ?? '#475569') }}22; color:{{ $dueCol[$dueStatus] ?? '#475569' }}">{{ $sh($dueLbl[$dueStatus] ?? ucfirst($dueStatus)) }}</span>@else — @endif</td>
        <td>@if($capa)<span class="badge" style="background:{{ ($capaCol[$capa] ?? '#475569') }}22; color:{{ $capaCol[$capa] ?? '#475569' }}">{{ $sh(ucfirst($capa)) }}</span>@else — @endif</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ $sh(__('visitors.no_violations')) }}</p>
@endif

{{-- 8. CAPA / action plan --}}
<h2>{{ $sh(__('visitors.corrective_action_plan')) }}</h2>
@if($report['capa']['actions']->count())
<table style="font-size:9px">
    <thead><tr><th>#</th><th>{{ $sh(__('visitors.item')) }}</th><th>{{ $sh(__('visitors.severity')) }}</th><th>{{ $sh(__('visitors.root_cause')) }}</th><th>{{ $sh(__('visitors.immediate_action')) }}</th><th>{{ $sh(__('visitors.corrective_action')) }}</th><th>{{ $sh(__('visitors.preventive_action')) }}</th><th>{{ $sh(__('visitors.responsible')) }}</th><th>{{ $sh(__('visitors.period')) }}</th><th>{{ $sh(__('visitors.due_date')) }}</th><th>{{ $sh(__('visitors.due_status')) }}</th><th>{{ $sh(__('visitors.status')) }}</th></tr></thead>
    <tbody>
    @foreach($report['capa']['actions'] as $i => $entry)
    @php
        $a = $entry->action;
        $vi = $a->visitItem;
    @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $sh($vi?->item_title ?: '—') }}</td>
        <td><span class="badge" style="background:{{ ($sev[$vi?->severity] ?? '#475569') }}22; color:{{ $sev[$vi?->severity] ?? '#475569' }}">{{ $sh(ucfirst($vi?->severity ?? '—')) }}</span></td>
        <td>{{ $sh($vi?->rootCause?->name ?: '—') }}</td>
        <td>{{ $sh($a->immediate_action ?: '—') }}</td>
        <td>{{ $sh($a->corrective_action ?: '—') }}</td>
        <td>{{ $sh($a->preventive_action ?: '—') }}</td>
        <td>{{ $sh($a->responsible?->name ?: ($vi?->responsible ?: '—')) }}</td>
        <td>{{ $sh($entry->periodLabel ?: '—') }}</td>
        <td>{{ $a->due_at?->format('d/m/Y H:i') ?: '—' }}</td>
        <td><span class="badge" style="background:{{ ($dueCol[$entry->dueStatus] ?? '#475569') }}22; color:{{ $dueCol[$entry->dueStatus] ?? '#475569' }}">{{ $sh($dueLbl[$entry->dueStatus] ?? ucfirst($entry->dueStatus)) }}</span></td>
        <td><span class="badge" style="background:{{ ($capaCol[$entry->status] ?? '#475569') }}22; color:{{ $capaCol[$entry->status] ?? '#475569' }}">{{ $sh(ucfirst($entry->status)) }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<p class="muted">{{ $sh(__('visitors.no_capa')) }}</p>
@endif

{{-- 9. Report footer --}}
<div class="footer">
    <strong>{{ $sh($company) }}</strong><br>
    {{ $sh(__('visitors.report_generated_at')) }}: {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
</div>
</body>
</html>
