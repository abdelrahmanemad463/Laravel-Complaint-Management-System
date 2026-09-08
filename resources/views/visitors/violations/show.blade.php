@extends('layouts.app')
@section('content')
@php
$vi = $violation;
$item = $vi->visitItem;
$visit = $vi->visit;
$status = $vi->effectiveStatus();
$dueStatus = $vi->dueStatus();
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
$canReview = auth()->user()->can('visit.resolution.review');
$canApprove = auth()->user()->can('visit.resolution.approve');
$canReject = auth()->user()->can('visit.resolution.reject');
$canSubmitResolve = auth()->user()->can('visit.resolution.submit');
$selfApproval = $vi->submitted_by !== null && (int) $vi->submitted_by === (int) auth()->id();
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('visitors.violations') }}" class="back-link">← {{ __('visitors.follow_up_report') }}</a>
        <h1 class="page-title mt-3">{{ __('visitors.violation') }} #V-{{ $vi->id }}</h1>
        <p class="page-subtitle">{{ $item?->item_code }} — {{ $item?->item_title }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <span class="badge" style="--badge-color:{{ $statusColor[$status] ?? '#475569' }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
        <span class="badge" style="--badge-color:{{ $dueStatusColor[$dueStatus] ?? '#475569' }}">{{ ucfirst(str_replace('_', ' ', $dueStatus)) }}</span>
    </div>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('common.branch') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $visit?->branch?->localized_name ?: '—' }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.violation_created') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $vi->created_at?->format('d/m/Y H:i') }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.due_date') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $vi->due_at?->format('d/m/Y H:i') ?: '—' }}</div></div>
    <div class="card"><div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.inspector') }}</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $visit?->inspector?->name ?: '—' }}</div></div>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2">
    <div class="card">
        <h3 class="section-title mb-3">{{ __('visitors.violation_details') }}</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.severity') }}</dt><dd class="min-w-0"><span class="badge" style="--badge-color:{{ $severityBadge[$item?->severity] ?? '#475569' }}">{{ ucfirst($item?->severity ?? '—') }}</span></dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.section') }}</dt><dd class="min-w-0 text-slate-800">{{ $item?->section_name ?: '—' }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.root_cause') }}</dt><dd class="min-w-0 text-slate-800">{{ $item?->rootCause?->name ?: '—' }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.notes') }}</dt><dd class="min-w-0 text-slate-800 break-words">{{ $item?->note ?: '—' }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.deduction_score') }}</dt><dd class="min-w-0 text-slate-800">-{{ $item?->deduction_score ?? 0 }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.responsible') }}</dt><dd class="min-w-0 text-slate-800">{{ $vi->responsible?->name ?: ($item?->responsible ?: '—') }}</dd></div>
        </dl>
    </div>
    <div class="card">
        <h3 class="section-title mb-3">{{ __('visitors.approved_actions') }}</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.immediate_action') }}</dt><dd class="min-w-0 text-slate-800 break-words">{{ $vi->immediate_action ?: '—' }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.corrective_action') }}</dt><dd class="min-w-0 text-slate-800 break-words">{{ $vi->corrective_action ?: '—' }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.preventive_action') }}</dt><dd class="min-w-0 text-slate-800 break-words">{{ $vi->preventive_action ?: '—' }}</dd></div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40">{{ __('visitors.period') }}</dt><dd class="min-w-0 text-slate-800">{{ $vi->periodLabel }}</dd></div>
        </dl>
    </div>
</div>

{{-- Evidence photos --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.evidence') }}</h3>
    @if($vi->photos->count())
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($vi->photos as $photo)
        <a href="{{ route('visitors.photos.serve', $photo) }}" target="_blank" class="block rounded-xl border border-slate-200 p-2 hover:border-indigo-300">
            <img src="{{ route('visitors.photos.serve', $photo) }}" alt="{{ $photo->original_name }}" class="h-32 w-full rounded-lg object-cover" loading="lazy">
            <div class="mt-1.5 flex items-center justify-between gap-1">
                <span class="min-w-0 truncate text-[11px] text-slate-500">{{ $photo->original_name }}</span>
                @if($photo->isResolution())
                <span class="shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">{{ __('visitors.resolution') }}</span>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @else
    <p class="text-sm text-slate-500">{{ __('visitors.no_evidence') }}</p>
    @endif
</div>

@if($vi->reject_reason)
<div class="mb-8 rounded-xl border border-rose-200 bg-rose-50 p-4 sm:p-6">
    <div class="text-sm font-bold text-rose-700">{{ __('visitors.reject_reason') }}</div>
    <p class="mt-1 text-sm text-rose-600">{{ $vi->reject_reason }}</p>
</div>
@endif

{{-- Reviewer actions / resolution submissions --}}
@if(in_array($status, ['pending_review','open','in_progress'], true))
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.review_actions') }}</h3>

    @if($status === 'pending_review')
        @if($canReview)
        {{-- Submitted resolution details shown to the reviewer --}}
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/60 p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge" style="--badge-color:#d97706">{{ __('visitors.pending_review_capa') }}</span>
                <span class="text-xs text-slate-500">{{ __('visitors.pending_review_hint') }}</span>
            </div>
            <dl class="mt-4 grid gap-3 gap-x-8 text-sm sm:grid-cols-2">
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('visitors.submitted_by') }}</dt>
                    <dd class="text-slate-800">{{ $vi->submitter?->name ?: '—' }}</dd>
                </div>
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('visitors.submitted_at') }}</dt>
                    <dd class="text-slate-800">{{ $vi->submitted_review_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                </div>
            </dl>
            @if($vi->resolution_note)
            <div class="mt-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('visitors.resolution_note') }}</div>
                <p class="mt-1 text-sm break-words text-slate-700">{{ $vi->resolution_note }}</p>
            </div>
            @endif
            @php $resolutionEvidence = $vi->photos->where('evidence_role', 'resolution'); @endphp
            @if($resolutionEvidence->count())
            <div class="mt-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('visitors.resolution_evidence') }}</div>
                <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    @foreach($resolutionEvidence as $photo)
                    <a href="{{ route('visitors.photos.serve', $photo) }}" target="_blank" class="block rounded-lg border border-slate-200 p-1 hover:border-indigo-300">
                        <img src="{{ route('visitors.photos.serve', $photo) }}" alt="{{ $photo->original_name }}" class="h-20 w-full rounded-md object-cover" loading="lazy">
                        <div class="mt-1 truncate text-[10px] text-slate-500">{{ $photo->original_name }}</div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @if($canApprove && !$selfApproval)
            {{-- Approve & Close --}}
            <form method="POST" action="{{ route('visitors.violations.approve', $vi) }}" onsubmit="return confirm('{{ __('visitors.approve_confirm') }}')">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="form-label">{{ __('visitors.closure_note') }}</label>
                        <textarea name="comment" class="form-input" rows="2" placeholder="{{ __('visitors.closure_note_placeholder') }}"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">{{ __('visitors.approve_close') }}</button>
                </div>
            </form>
            @elseif($canApprove && $selfApproval)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">{{ __('visitors.cannot_self_approve') }}</div>
            @endif

            @if($canReject)
            {{-- Reject --}}
            <form method="POST" action="{{ route('visitors.violations.reject', $vi) }}" onsubmit="return confirm('{{ __('visitors.reject_confirm') }}')">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="form-label">{{ __('visitors.reject_reason') }} *</label>
                        <textarea name="reject_reason" class="form-input" rows="2" required placeholder="{{ __('visitors.reject_reason_placeholder') }}"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-xl border border-rose-300 bg-white px-4 py-3 text-sm font-bold text-rose-700 hover:bg-rose-50">{{ __('visitors.reject_violation') }}</button>
                </div>
            </form>
            @endif
        </div>
        @else
        <p class="text-sm text-slate-500">{{ __('visitors.resolution_submitted_no_access') }}</p>
        @endif

    @elseif(in_array($status, ['open', 'in_progress'], true) && $canSubmitResolve)
    {{-- Inspector / reviewer resolve --}}
    <form method="POST" action="{{ route('visitors.violations.resolve', $vi) }}" enctype="multipart/form-data" onsubmit="return confirm('{{ __('visitors.resolve_confirm') }}')">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="form-label">{{ __('visitors.resolution_note') }}</label>
                <textarea name="note" class="form-input" rows="2" placeholder="{{ __('visitors.resolution_note_placeholder') }}"></textarea>
            </div>
            <div>
                <label class="form-label flex items-center gap-2">
                    <span>{{ __('visitors.resolution_evidence_photo') }}</span>
                    @if(strtolower((string) $item?->severity) === 'critical')
                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">*</span>
                    @endif
                </label>
                <input type="file" name="photo" class="form-input" accept="image/jpeg,image/png,image/webp" @if(strtolower((string) $item?->severity) === 'critical') required @endif>
                <p class="mt-1 text-xs text-slate-500">{{ __('visitors.photo_help') }}</p>
            </div>
            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white hover:bg-indigo-700">{{ __('visitors.submit_resolution') }}</button>
        </div>
    </form>
    @endif
</div>
@endif

{{-- Follow-up timeline --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.follow_up_timeline') }}</h3>
    @if($vi->updates->count())
    <div class="space-y-4">
        @foreach($vi->updates->sortByDesc('created_at') as $update)
        @php
            $eventColor = match($update->status) {
                'open' => 'border-l-blue-600',
                'in_progress' => 'border-l-violet-600',
                'pending_review' => 'border-l-amber-600',
                'closed' => 'border-l-emerald-600',
                'rejected' => 'border-l-rose-600',
                default => 'border-l-slate-400',
            };
        @endphp
        <div class="border-l-4 {{ $eventColor }} pl-4 py-2">
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="font-bold uppercase tracking-wide text-slate-700">{{ ucfirst(str_replace('_', ' ', $update->status)) }}</span>
                <span>·</span>
                <span>{{ $update->created_at?->format('d/m/Y H:i') }}</span>
                <span>·</span>
                <span>{{ $update->user?->name ?: '—' }}</span>
            </div>
            @if($update->comment)
            <p class="mt-1 text-sm text-slate-700 break-words">{{ $update->comment }}</p>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <p class="text-sm text-slate-500">{{ __('visitors.no_follow_ups') }}</p>
    @endif
</div>
{{-- Follow-up history (records that addressed this violation) --}}
<div class="mb-8 card p-4 sm:p-6">
    <h3 class="section-title mb-4">{{ __('visitors.related_follow_ups') }}</h3>
    @if($vi->followUps->count())
    <div class="space-y-4">
        @foreach($vi->followUps->sortByDesc('followed_up_at') as $fu)
        <div class="flex flex-wrap items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-{{ $fu->isResolved() ? 'emerald' : 'amber' }}-600 px-2 py-0.5 text-[11px] font-bold text-white">{{ $fu->isResolved() ? __('visitors.follow_up_resolved') : __('visitors.follow_up_still_open') }}</span>
                    <span class="text-xs text-slate-500">{{ $fu->followed_up_at?->format('d/m/Y H:i') }}</span>
                    @if($fu->visit)
                    <a href="{{ route('visitors.reports.show', $fu->visit) }}" class="text-xs font-bold text-indigo-600 hover:underline">{{ __('visitors.visit_number', ['id' => $fu->visit_id]) }}</a>
                    @endif
                    @if($fu->performer)
                    <span class="text-xs text-slate-500">· {{ $fu->performer->name }}</span>
                    @endif
                </div>
                @if($fu->follow_up_note)
                <p class="mt-1.5 text-sm text-slate-700 break-words">{{ $fu->follow_up_note }}</p>
                @endif
                @if($fu->photos->count())
                <div class="mt-2 text-xs text-amber-700">{{ __('visitors.follow_up_photos_count', ['count' => $fu->photos->count()]) }}</div>
                @endif
            </div>
            @if($fu->photos->count())
            <div class="flex shrink-0 flex-wrap gap-1">
                @foreach($fu->photos->take(4) as $photo)
                <a href="{{ route('visitors.photos.serve', $photo) }}" target="_blank">
                    <img src="{{ route('visitors.photos.serve', $photo) }}" alt="{{ $photo->original_name }}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" loading="lazy">
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <p class="text-sm text-slate-500">{{ __('visitors.no_follow_ups') }}</p>
    @endif
</div>
@endsection