@extends('layouts.app')
@section('content')
@php
    $totalItems = $visit->items->count();
    $reviewedCount = $visit->items->filter->isReviewed()->count();
    $progressPct = $totalItems ? round($reviewedCount / $totalItems * 100) : 0;
@endphp
<div class="mb-6 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
    <div class="min-w-0">
        <a href="{{ $visit->isCompleted() ? route('visitors.home') : route('visitors.open') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
        <h1 class="page-title mt-3 truncate">{{ $visit->branch?->name }}</h1>
        <p class="page-subtitle truncate">{{ $visit->visitType?->name }} • {{ $visit->visit_date?->format('Y-m-d') }}</p>
        <p class="page-subtitle mt-1 truncate">{{ __('visitors.inspector') }}: {{ $visit->inspector?->name }}</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-center shadow-sm sm:px-5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-xs">{{ __('visitors.progress') }}</div>
                <div id="visit-progress-text" class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">{{ $reviewedCount }} / {{ $totalItems }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ __('visitors.items_reviewed') }}</div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div id="visit-progress-bar" class="h-full rounded-full bg-indigo-600 transition-all duration-300" style="width: {{ $progressPct }}%"></div>
                </div>
                <div class="mt-1 text-[10px] font-semibold text-slate-500">{{ $progressPct }}%</div>
            </div>
        </div>
        @if($visit->isCompleted())
        <span class="badge self-start sm:self-auto" style="--badge-color:#16a34a">{{ __('visitors.completed') }}</span>
        @endif
    </div>
</div>

<div id="visit-autosave-status" class="mb-4 hidden rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold shadow-sm"></div>

{{-- Mobile section selector (dropdown) --}}
<div class="mb-4 sm:hidden">
    <label for="section-jump-mobile" class="form-label text-xs">{{ __('visitors.section') ?? 'Section' }}</label>
    <select id="section-jump-mobile" class="form-input">
        @foreach($grouped as $sectionName => $sectionItems)
            <option value="section-{{ Str::slug($sectionName) }}">{{ $sectionName }} ({{ $sectionItems->filter->isReviewed()->count() }}/{{ $sectionItems->count() }})</option>
        @endforeach
    </select>
</div>

{{-- Section navigation pills - horizontally scrollable on mobile --}}
<div id="section-tabs" class="mb-6 -mx-3 flex flex-nowrap gap-2 overflow-x-auto px-3 pb-2 snap-x snap-mandatory scrollbar-thin sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0">
    @foreach($grouped as $sectionName => $sectionItems)
        <button type="button" class="section-tab snap-start shrink-0 whitespace-nowrap rounded-full border bg-white px-3 py-2.5 text-sm font-semibold transition sm:px-4" data-target="section-{{ Str::slug($sectionName) }}">
            <span class="section-tab-name">{{ $sectionName }}</span>
            <span class="ms-1 text-xs opacity-70 section-tab-count">{{ $sectionItems->filter->isReviewed()->count() }}/{{ $sectionItems->count() }}</span>
        </button>
    @endforeach
</div>

{{-- Checklist items grouped by section --}}
<form id="visit-checklist" class="pb-24 sm:pb-0">
@csrf
<div class="space-y-8 sm:space-y-10">
    @foreach($grouped as $sectionName => $sectionItems)
    <section id="section-{{ Str::slug($sectionName) }}" class="visit-section scroll-mt-24">
        <h2 class="section-title mb-3 flex items-center justify-between gap-3">
            <span class="min-w-0 truncate">{{ $sectionName }}</span>
            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 sm:text-sm sm:font-normal section-head-count">{{ $sectionItems->filter->isReviewed()->count() }}/{{ $sectionItems->count() }}</span>
        </h2>
        <div class="space-y-4">
            @foreach($sectionItems->sortBy('sort_order') as $itemIndex => $item)
            @php
                $itemFollowUps = $followUpCandidates->get($item->checklist_item_id, collect());
                $followUpJson = $itemFollowUps->values()->toJson();
            @endphp
            <article class="card visit-item !p-4 sm:!p-6" data-status="{{ $item->status }}" data-visited="{{ $item->visited_at ? '1' : '0' }}" data-item-id="{{ $item->id }}" data-critical="{{ $item->isCritical() ? '1' : '0' }}" data-checklist-id="{{ $item->checklist_item_id }}" data-follow-up-count="{{ $itemFollowUps->count() }}" data-follow-up-data='{{ $followUpJson }}'>
                <div class="mb-3 flex flex-wrap items-start gap-2">
                    <span class="badge shrink-0" style="--badge-color:#475569">{{ $item->item_code }}</span>
                    <span class="badge shrink-0" style="--badge-color:{{ $item->isCritical() ? '#dc2626' : ($item->severity === 'major' ? '#ea580c' : '#16a34a') }}">{{ ucfirst($item->severity) }}</span>
                    <span class="min-w-0 flex-1 text-sm font-semibold leading-snug text-slate-900 sm:text-base">{{ $item->item_title }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <button type="button" class="status-btn status-ok rounded-xl border text-sm font-bold touch-manipulation" data-value="ok" @disabled($visit->isCompleted())>{{ __('visitors.option_ok') }}</button>
                    <button type="button" class="status-btn status-nc rounded-xl border text-sm font-bold touch-manipulation" data-value="nc" @disabled($visit->isCompleted())>{{ __('visitors.option_nc') }}</button>
                    <button type="button" class="status-btn status-na rounded-xl border text-sm font-bold touch-manipulation" data-value="na" @disabled($visit->isCompleted())>{{ __('visitors.option_na') }}</button>
                    @if($itemFollowUps->isNotEmpty() && !$visit->isCompleted())
                    <button type="button" class="follow-up-open-btn inline-flex items-center justify-center gap-1 rounded-xl border border-amber-500 bg-amber-50 px-3 py-2 text-sm font-bold text-amber-800 transition hover:bg-amber-100" @disabled($visit->isCompleted())>
                        <span>{{ __('visitors.follow_up_violation') }}</span>
                        <span class="follow-up-count-badge rounded-full bg-amber-600 px-1.5 text-xs font-black text-white">{{ $itemFollowUps->count() }}</span>
                    </button>
                    @endif
                </div>

                {{-- Saved follow-ups for this item (this visit) --}}
                <div class="follow-up-records mt-3 space-y-2 {{ $item->followUps->count() ? '' : 'hidden' }}">
                    @foreach($item->followUps->sortByDesc('followed_up_at') as $fu)
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs text-amber-800">
                        <span class="font-bold">{{ __('visitors.follow_up_recorded') }}</span>
                        <span class="font-semibold">{{ $fu->violations->map(fn ($v) => '#V-'.$v->id)->join(', ') }}</span>
                        <span class="rounded-full bg-{{ $fu->isResolved() ? 'emerald' : 'amber' }}-600 px-2 py-0.5 font-bold text-white">{{ $fu->isResolved() ? __('visitors.follow_up_resolved') : __('visitors.follow_up_still_open') }}</span>
                        <span class="text-amber-600">{{ $fu->followed_up_at?->format('d/m/Y H:i') }}</span>
                        @if($fu->photos->count())
                        <span class="text-amber-600">{{ __('visitors.follow_up_photos_count', ['count' => $fu->photos->count()]) }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Non-compliant details panel --}}
                <div class="nc-panel {{ $item->status === 'nc' ? '' : 'hidden' }} mt-4 space-y-4 rounded-xl border border-rose-200 bg-rose-50 p-3 sm:p-4">
                    <div>
                        <label class="form-label">{{ __('visitors.severity') }}</label>
                        <input type="text" class="form-input bg-slate-100" value="{{ ucfirst($item->severity) }} ({{ $item->deduction_score }} pts)" readonly disabled>
                    </div>
                    <div>
                        <label class="form-label" for="root_cause_{{ $item->id }}">{{ __('visitors.root_cause') }} *</label>
                        <select class="form-input rc-select" id="root_cause_{{ $item->id }}" @disabled($visit->isCompleted())>
                            <option value="">{{ __('common.select') }}</option>
                            @foreach($rootCauses as $rc)
                            <option value="{{ $rc->id }}" @selected($item->root_cause_id === $rc->id)>{{ $rc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="note_{{ $item->id }}">{{ __('visitors.notes') }}</label>
                        <textarea class="form-input note-input" id="note_{{ $item->id }}" rows="3" @disabled($visit->isCompleted())>{{ $item->note }}</textarea>
                    </div>

                    @if($item->support_department || true)
                    <div>
                        <label class="form-label" for="dept_{{ $item->id }}">{{ __('visitors.support_department') }}</label>
                        <select class="form-input dept-select" id="dept_{{ $item->id }}" @disabled($visit->isCompleted())>
                            <option value="">{{ __('common.select') }}</option>
                            @foreach($supportDepartments as $dept)
                            <option value="{{ $dept }}" @selected($item->support_department === $dept)>{{ __("visitors.support_dept_$dept") }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Existing open violation follow-up: handled by the
                         Follow-up Violation button + modal (see above) --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                        <label class="form-label flex items-center gap-2">
                            <span>{{ __('visitors.evidence_photo') }}</span>
                            @if($item->isCritical())<span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">*</span>@endif
                        </label>
                        @if($item->isCritical())
                        <div class="photo-required-msg {{ $item->status === 'nc' ? '' : 'hidden' }} mb-3 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5">
                            <span class="shrink-0 text-rose-600">📷</span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-rose-700">{{ __('visitors.evidence_photo') }} * — {{ __('visitors.photo_required_critical') }}</p>
                                <p class="mt-0.5 text-[11px] text-rose-600">{{ __('visitors.photo_help') }}</p>
                            </div>
                        </div>
                        @else
                        <p class="mb-3 text-xs text-slate-500">{{ __('visitors.photo_help') }}</p>
                        @endif
                        <input type="file" class="photo-input hidden" accept="image/jpeg,image/png,image/webp,image/*" @if($visit->isCompleted()) disabled @endif>
                        @unless($visit->isCompleted())
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                            <button type="button" class="photo-cam-btn inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 active:bg-indigo-800" data-role="camera">
                                <span>📷</span> <span>{{ app()->getLocale()==='ar' ? 'التقاط صورة' : 'Take Photo' }}</span>
                            </button>
                            <button type="button" class="photo-upload-btn inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" data-role="gallery">
                                <span>🖼️</span> <span>{{ app()->getLocale()==='ar' ? 'رفع صورة' : 'Upload Photo' }}</span>
                            </button>
                        </div>
                        @endunless
                        <div class="mt-3 flex flex-wrap gap-2 photo-list">
                            @foreach($item->photos as $photo)
                            @php $isFu = $photo->evidence_role === 'follow_up'; @endphp
                            <a href="{{ route('visitors.photos.serve', $photo) }}" target="_blank" class="{{ $isFu ? 'follow-up-evidence inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100' : 'inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100' }}">{{ $photo->original_name }}@if($isFu)<span class="rounded-full bg-amber-600 px-1.5 text-[10px] font-black text-white">{{ __('visitors.follow_up_violation') }}</span>@endif</a>
                            @endforeach
                        </div>
                        @if($item->isCritical())
                        <p class="mt-2 hidden text-xs text-slate-500 sm:block">{{ __('visitors.photo_help') }}</p>
                        @endif
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <div class="text-sm font-bold text-slate-900">{{ __('visitors.approved_actions') }}</div>
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40 sm:normal-case sm:tracking-normal">{{ __('visitors.immediate_action') }}</dt><dd class="min-w-0 break-words text-slate-800">{{ $item->immediate_action ?: '—' }}</dd></div>
                            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40 sm:normal-case sm:tracking-normal">{{ __('visitors.corrective_action') }}</dt><dd class="min-w-0 break-words text-slate-800">{{ $item->corrective_action ?: '—' }}</dd></div>
                            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40 sm:normal-case sm:tracking-normal">{{ __('visitors.preventive_action') }}</dt><dd class="min-w-0 break-words text-slate-800">{{ $item->preventive_action ?: '—' }}</dd></div>
                            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40 sm:normal-case sm:tracking-normal">{{ __('visitors.responsible') }}</dt><dd class="min-w-0 break-words text-slate-800">{{ $item->responsible ?: '—' }}</dd></div>
                            <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"><dt class="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40 sm:normal-case sm:tracking-normal">{{ __('visitors.period') }}</dt><dd class="min-w-0 break-words text-slate-800">{{ $item->period_hours !== null ? \App\Services\Visitors\DueDateService::hoursLabel($item->period_hours) : '—' }}</dd></div>
                        </dl>
                    </div>

                    <div class="text-xs text-slate-500">{{ __('visitors.deduction_score') }}: -{{ $item->deduction_score }}</div>
                </div>
            </article>
            @endforeach
        </div>
    </section>
    @endforeach
</div>
</form>

{{-- Bottom submit (desktop; mobile uses the sticky bottom bar) --}}
@unless($visit->isCompleted())
<form id="submit-visit-form" method="POST" action="{{ route('visitors.submit', $visit) }}" class="mt-8 hidden justify-end sm:flex">
    @csrf
    <button type="submit" class="btn-primary px-8 py-3 text-base">{{ __('visitors.submit_visit') }}</button>
</form>
@endunless

{{-- Sticky mobile bottom bar --}}
@unless($visit->isCompleted())
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur supports-[backdrop-filter]:bg-white/90 sm:hidden">
    <div class="flex items-center gap-2">
        <div class="min-w-0 flex-1">
            <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.progress') }}</div>
            <div class="text-sm font-black text-slate-900" id="visit-progress-text-mobile">{{ $reviewedCount }} / {{ $totalItems }}</div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100"><div id="visit-progress-bar-mobile" class="h-full bg-indigo-600 transition-all" style="width: {{ $progressPct }}%"></div></div>
        </div>
        <button type="button" id="scroll-top-btn" class="shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-bold text-slate-700">↑</button>
        <button type="submit" form="submit-visit-form" class="shrink-0 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow">{{ __('visitors.submit_visit') }}</button>
    </div>
    <div id="visit-autosave-status-mobile" class="mt-2 hidden text-center text-xs font-semibold"></div>
</div>
@endunless

{{-- Follow-up modal: multi-select previous violations, optional note/photos --}}
<div id="follow-up-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true">
    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-black text-slate-900">
                {{ __('visitors.follow_up_modal_title') }}
                <span class="ms-1 text-sm font-bold text-slate-400" id="follow-up-modal-item-ref"></span>
            </h3>
            <button type="button" id="follow-up-modal-close" class="rounded-full bg-slate-100 px-3 py-1 font-bold text-slate-600" aria-label="{{ __('common.close') }}">✕</button>
        </div>
        <form id="follow-up-form" class="flex min-h-0 flex-1 flex-col" enctype="multipart/form-data">
            @csrf
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <div id="follow-up-form-msg" class="hidden rounded-lg bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700"></div>
                <div>
                    <label class="form-label">{{ __('visitors.follow_up_violations') }} *</label>
                    <p class="mb-2 text-xs text-slate-500">{{ __('visitors.follow_up_violations_help') }}</p>
                    <div id="follow-up-violation-list" class="space-y-2"></div>
                </div>
                <div>
                    <label class="form-label">{{ __('visitors.follow_up_result') }}</label>
                    <select name="result" id="follow-up-result" class="form-input">
                        <option value="resolved">{{ __('visitors.follow_up_resolved') }}</option>
                        <option value="still_open">{{ __('visitors.follow_up_still_open') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="follow-up-note">{{ __('visitors.follow_up_note') }}</label>
                    <textarea name="note" id="follow-up-note" class="form-input" rows="3" placeholder="{{ __('visitors.follow_up_note_placeholder') }}"></textarea>
                </div>
                <div>
                    <label class="form-label">{{ __('visitors.follow_up_evidence') }}</label>
                    <input type="file" name="photos[]" id="follow-up-photos" class="form-input" accept="image/jpeg,image/png,image/webp,image/*" multiple>
                    <p class="mt-1 text-xs text-slate-500">{{ __('visitors.photo_help') }}</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                <button type="button" id="follow-up-modal-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">{{ __('common.cancel') }}</button>
                <button type="submit" class="rounded-xl bg-amber-600 px-5 py-2 text-sm font-bold text-white hover:bg-amber-700">{{ __('visitors.follow_up_save') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(() => {
    if (document.querySelector('.visit-item[data-visited="1"]') === null && document.querySelectorAll('.visit-item').length === 0) return;

    const statusEl = document.getElementById('visit-autosave-status');
    const statusElMobile = document.getElementById('visit-autosave-status-mobile');

    const setStatus = (text, kind) => {
        [statusEl, statusElMobile].forEach((el) => {
            if (!el) return;
            el.classList.remove('hidden');
            el.textContent = text;
            el.classList.remove('text-emerald-700', 'text-slate-500', 'text-emerald-600');
            el.classList.add(kind === 'saved' ? 'text-emerald-700' : 'text-slate-500');
        });
        setTimeout(() => {
            statusEl?.classList.add('hidden');
            statusElMobile?.classList.add('hidden');
        }, 1800);
    };

    const csrf = document.querySelector('input[name="_token"]')?.value;

    const refreshCounts = () => {
        const items = document.querySelectorAll('.visit-item');
        let reviewed = 0;
        items.forEach((el) => { if (el.dataset.visited === '1') reviewed++; });
        const total = items.length;
        const pct = total ? Math.round(reviewed/total*100) : 0;
        const progressText = document.getElementById('visit-progress-text');
        const progressBar = document.getElementById('visit-progress-bar');
        const progressTextM = document.getElementById('visit-progress-text-mobile');
        const progressBarM = document.getElementById('visit-progress-bar-mobile');
        if (progressText) progressText.textContent = `${reviewed} / ${total}`;
        if (progressBar) progressBar.style.width = pct + '%';
        if (progressTextM) progressTextM.textContent = `${reviewed} / ${total}`;
        if (progressBarM) progressBarM.style.width = pct + '%';
        document.querySelectorAll('.visit-section').forEach((section) => {
            const itemsInSection = section.querySelectorAll('.visit-item');
            let r = 0;
            itemsInSection.forEach((el) => { if (el.dataset.visited === '1') r++; });
            const headCount = section.querySelector('.section-head-count');
            if (headCount) headCount.textContent = `${r}/${itemsInSection.length}`;
        });
        document.querySelectorAll('.section-tab').forEach((tab) => {
            const section = document.getElementById(tab.dataset.target);
            if (!section) return;
            let r = 0;
            const itemsInSection = section.querySelectorAll('.visit-item');
            itemsInSection.forEach((el) => { if (el.dataset.visited === '1') r++; });
            const countEl = tab.querySelector('.section-tab-count');
            if (countEl) countEl.textContent = `${r}/${itemsInSection.length}`;
        });
        // sync mobile select
        const sel = document.getElementById('section-jump-mobile');
        if (sel) {
            [...sel.options].forEach((opt) => {
                const sec = document.getElementById(opt.value);
                if (!sec) return;
                const n = sec.querySelectorAll('.visit-item').length;
                let r2 = 0;
                sec.querySelectorAll('.visit-item').forEach((el) => { if (el.dataset.visited === '1') r2++; });
                const name = opt.textContent.split(' (')[0];
                opt.textContent = `${name} (${r2}/${n})`;
            });
        }
    };

    const saveItem = async (itemEl, payload) => {
        if (itemEl.querySelector('.status-btn')?.disabled) return;
        const id = itemEl.dataset.itemId;
        const body = new FormData();
        Object.entries(payload).forEach(([k, v]) => { if (v !== undefined && v !== null) body.append(k, v); });
        body.append('_method', 'PUT');
        body.append('_token', csrf);
        setStatus('{{ __('visitors.saving') }}', 'saving');
        try {
            const res = await fetch(`{{ url('/visitors/items') }}/${id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body,
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'save failed');
            itemEl.dataset.visited = '1';
            itemEl.dataset.status = data.status;
            refreshCounts();
            setStatus('{{ __('visitors.saved') }}', 'saved');
        } catch (e) {
            setStatus(e.message || '{{ __('visitors.save_failed') }}', 'saving');
        }
    };
    // Follow-up modal wiring
    const followUpModal = document.getElementById('follow-up-modal');
    const followUpForm = document.getElementById('follow-up-form');
    const followUpList = document.getElementById('follow-up-violation-list');
    const followUpMsg = document.getElementById('follow-up-form-msg');
    let followUpItemEl = null;

    const closeFollowUpModal = () => {
        followUpModal.classList.add('hidden');
        followUpModal.classList.remove('flex');
        followUpItemEl = null;
    };

    const openFollowUpModal = (itemEl) => {
        followUpMsg.classList.add('hidden');
        followUpForm.reset();
        followUpList.innerHTML = '';
        followUpItemEl = itemEl;
        let candidates = [];
        try { candidates = JSON.parse(itemEl.dataset.followUpData || '[]'); } catch (e) { candidates = []; }
        if (candidates.length === 0) return;
        document.getElementById('follow-up-modal-item-ref').textContent = itemEl.dataset.itemCode || '';
        candidates.forEach((c, idx) => {
            const label = document.createElement('label');
            label.className = 'flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3 transition hover:border-amber-300';
            const info = document.createElement('span');
            info.className = 'min-w-0 flex-1';
            const head = document.createElement('span');
            head.className = 'flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800';
            const vio = document.createElement('span');
            vio.className = 'rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-black text-amber-800';
            vio.textContent = '#V-' + c.id;
            head.appendChild(vio);
            const code = document.createElement('span');
            code.className = 'text-[11px] font-bold text-slate-500';
            code.textContent = c.itemCode;
            head.appendChild(code);
            if (c.itemTitle) {
                const title = document.createElement('span');
                title.className = 'min-w-0 flex-1 break-words text-xs font-normal text-slate-600';
                title.textContent = c.itemTitle;
                head.appendChild(title);
            }
            info.appendChild(head);
            const meta = document.createElement('span');
            meta.className = 'mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-500';
            const cells = [
                `{{ __('visitors.original_visit') }}: ${c.originDate}`,
                `{{ __('visitors.status') }}: ${c.status.replace(/_/g, ' ')}`
            ];
            if (c.dueDate) cells.push(`{{ __('visitors.due_date') }}: ${c.dueDate}`);
            if (c.note) cells.push(c.note);
            meta.textContent = cells.join('  •  ');
            info.appendChild(meta);
            label.appendChild(info);
            if (c.photos && c.photos.length) {
                const thumbs = document.createElement('span');
                thumbs.className = 'flex shrink-0 flex-wrap gap-1';
                c.photos.slice(0, 4).forEach((url) => {
                    const img = document.createElement('img');
                    img.src = url; img.alt = ''; img.className = 'h-10 w-10 rounded-lg border border-slate-200 object-cover';
                    thumbs.appendChild(img);
                });
                if (c.photos.length > 4) {
                    const more = document.createElement('span');
                    more.className = 'self-center text-[10px] font-bold text-slate-400';
                    more.textContent = `+${c.photos.length - 4}`;
                    thumbs.appendChild(more);
                }
                label.appendChild(thumbs);
            }
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'violation_ids[]';
            input.value = c.id;
            input.checked = idx === 0;
            input.className = 'follow-up-vio-check mt-0.5 h-4 w-4 shrink-0 accent-amber-600';
            label.prepend(input);
            followUpList.appendChild(label);
        });
        followUpModal.classList.remove('hidden');
        followUpModal.classList.add('flex');
    };

    document.querySelectorAll('.follow-up-open-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const itemEl = btn.closest('.visit-item');
            if (!itemEl) return;
            openFollowUpModal(itemEl);
        });
    });
    document.getElementById('follow-up-modal-close')?.addEventListener('click', closeFollowUpModal);
    document.getElementById('follow-up-modal-cancel')?.addEventListener('click', closeFollowUpModal);
    followUpModal.addEventListener('click', (e) => { if (e.target === followUpModal) closeFollowUpModal(); });

    const appendFollowUpChip = (itemEl, data) => {
        const wrap = itemEl.querySelector('.follow-up-records');
        if (!wrap || !data || !data.follow_up) return;
        const chip = document.createElement('div');
        chip.className = 'flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs text-amber-800';
        const rec = document.createElement('span');
        rec.className = 'font-bold';
        rec.textContent = `{{ __('visitors.follow_up_recorded') }}`;
        chip.appendChild(rec);
        const ids = document.createElement('span');
        ids.className = 'font-semibold';
        ids.textContent = (data.follow_up.violation_ids || []).map((v) => '#V-' + v).join(', ');
        chip.appendChild(ids);
        const badge = document.createElement('span');
        const resolved = data.follow_up.result === 'resolved';
        badge.className = `rounded-full ${resolved ? 'bg-emerald-600' : 'bg-amber-600'} px-2 py-0.5 font-bold text-white`;
        badge.textContent = resolved ? `{{ __('visitors.follow_up_resolved') }}` : `{{ __('visitors.follow_up_still_open') }}`;
        chip.appendChild(badge);
        if (data.follow_up.photo_count) {
            const cnt = document.createElement('span');
            cnt.className = 'text-amber-600';
            cnt.textContent = `{{ __('visitors.follow_up_photos_count', ['count' => ':count']) }}`.replace(':count', data.follow_up.photo_count);
            chip.appendChild(cnt);
        }
        wrap.classList.remove('hidden');
        wrap.prepend(chip);
    };

    followUpForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!followUpItemEl) return;
        if (!followUpList.querySelector('.follow-up-vio-check:checked')) {
            followUpMsg.textContent = `{{ __('visitors.follow_up_no_selection') }}`;
            followUpMsg.classList.remove('hidden');
            return;
        }
        followUpMsg.classList.add('hidden');
        const submitBtn = followUpForm.querySelector('button[type="submit"]');
        const itemId = followUpItemEl.dataset.itemId;
        const fd = new FormData(followUpForm);
        submitBtn.disabled = true;
        try {
            const res = await fetch(`{{ url('/visitors/items') }}/${itemId}/follow-up`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || `{{ __('visitors.save_failed') }}`);
            appendFollowUpChip(followUpItemEl, data);
            setStatus(`{{ __('visitors.follow_up_saved') }}`, 'saved');
            closeFollowUpModal();
        } catch (err) {
            followUpMsg.textContent = err.message;
            followUpMsg.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
        }
    });

    const collectNcPayload = (itemEl) => {
        const rc = itemEl.querySelector('.rc-select')?.value || '';
        const note = itemEl.querySelector('.note-input')?.value || '';
        const dept = itemEl.querySelector('.dept-select')?.value || '';
        return { root_cause_id: rc, note, support_department: dept };
    };

    document.querySelectorAll('.visit-item').forEach((itemEl) => {
        const ncPanel = itemEl.querySelector('.nc-panel');
        const applyButtonStyles = () => {
            const current = itemEl.dataset.status;
            itemEl.querySelectorAll('.status-btn').forEach((btn) => {
                const active = btn.dataset.value === current;
                btn.classList.toggle('bg-emerald-600', active && current === 'ok');
                btn.classList.toggle('text-white', active && current === 'ok');
                btn.classList.toggle('border-emerald-600', active && current === 'ok');
                btn.classList.toggle('bg-rose-600', active && current === 'nc');
                btn.classList.toggle('border-rose-600', active && current === 'nc');
                btn.classList.toggle('bg-slate-600', active && current === 'na');
                btn.classList.toggle('border-slate-600', active && current === 'na');
                btn.classList.toggle('text-white', active && current !== 'ok');
                btn.classList.toggle('bg-white', !active);
                btn.classList.toggle('bg-slate-50', !active);
                btn.classList.toggle('border-slate-200', !active);
                btn.classList.toggle('text-slate-700', !active);
            });
        };
        applyButtonStyles();

        itemEl.querySelectorAll('.status-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const value = btn.dataset.value;
                itemEl.dataset.status = value;
                ncPanel.classList.toggle('hidden', value !== 'nc');
                const requiredMsg = itemEl.querySelector('.photo-required-msg');
                if (requiredMsg) requiredMsg.classList.toggle('hidden', !(itemEl.dataset.critical === '1' && value === 'nc'));
                if (value === 'nc') {
                    await saveItem(itemEl, { status: value, ...collectNcPayload(itemEl) });
                } else {
                    await saveItem(itemEl, { status: value });
                }
                applyButtonStyles();
            });
        });

        if (itemEl.querySelector('.rc-select')) {
            itemEl.querySelector('.rc-select').addEventListener('change', () => {
                saveItem(itemEl, { status: 'nc', ...collectNcPayload(itemEl) });
            });
        }
        const noteInput = itemEl.querySelector('.note-input');
        if (noteInput) {
            let saveTimer;
            noteInput.addEventListener('blur', () => {
                saveItem(itemEl, { status: 'nc', ...collectNcPayload(itemEl) });
            });
            noteInput.addEventListener('input', () => {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => saveItem(itemEl, { status: 'nc', ...collectNcPayload(itemEl) }), 900);
            });
        }
        const mkInput = itemEl.querySelector('.mk-input');
        if (mkInput) mkInput.addEventListener('change', () => saveItem(itemEl, { status: 'nc', ...collectNcPayload(itemEl) }));
        const deptSelect = itemEl.querySelector('.dept-select');
        if (deptSelect) deptSelect.addEventListener('change', () => saveItem(itemEl, { status: 'nc', ...collectNcPayload(itemEl) }));

        const photoInput = itemEl.querySelector('.photo-input');
        const camBtn = itemEl.querySelector('.photo-cam-btn');
        const uploadBtn = itemEl.querySelector('.photo-upload-btn');
        const triggerPhoto = (useCamera) => {
            if (!photoInput || photoInput.disabled) return;
            if (useCamera) {
                photoInput.setAttribute('capture', 'environment');
                photoInput.setAttribute('accept', 'image/*');
            } else {
                photoInput.removeAttribute('capture');
                photoInput.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/*');
            }
            photoInput.click();
        };
        camBtn?.addEventListener('click', () => triggerPhoto(true));
        uploadBtn?.addEventListener('click', () => triggerPhoto(false));
        if (photoInput) {
            photoInput.addEventListener('change', async () => {
                const file = photoInput.files[0];
                if (!file) return;
                const id = itemEl.dataset.itemId;
                const fd = new FormData();
                fd.append('photo', file);
                fd.append('_token', csrf);
                setStatus('{{ __('visitors.uploading') }}', 'saving');
                try {
                    const res = await fetch(`{{ url('/visitors/items') }}/${id}/photo`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: fd,
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) throw new Error(data.message || 'upload failed');
                    const list = itemEl.querySelector('.photo-list');
                    const link = document.createElement('a');
                    link.href = data.photo.url;
                    link.target = '_blank';
                    link.className = 'inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100';
                    link.textContent = data.photo.original_name;
                    list.appendChild(link);
                    setStatus('{{ __('visitors.saved') }}', 'saved');
                } catch (e) {
                    setStatus(e.message || '{{ __('visitors.save_failed') }}', 'saving');
                    photoInput.value = '';
                }
            });
        }
    });

    // Section tab navigation + mobile select
    document.querySelectorAll('.section-tab').forEach((tab) => {
        const activate = () => {
            const target = document.getElementById(tab.dataset.target);
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.querySelectorAll('.section-tab').forEach((t) => t.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600'));
            tab.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
        };
        tab.addEventListener('click', activate);
    });
    const mobileJump = document.getElementById('section-jump-mobile');
    if (mobileJump) {
        mobileJump.addEventListener('change', () => {
            const target = document.getElementById(mobileJump.value);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    document.getElementById('scroll-top-btn')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    const missingMessage = `{{ __('visitors.evidence_photo_required') }}`;

    const submitForm = document.getElementById('submit-visit-form');
    if (submitForm) {
        submitForm.addEventListener('submit', (e) => {
            const missing = [];
            document.querySelectorAll('.visit-item').forEach((itemEl) => {
                if (itemEl.dataset.critical !== '1') return;
                if (itemEl.dataset.status !== 'nc') return;
                const hasInitial = itemEl.querySelectorAll('.photo-list a:not(.follow-up-evidence)').length > 0;
                if (!hasInitial) missing.push(itemEl.dataset.itemId);
            });
            if (missing.length > 0) {
                e.preventDefault();
                alert(missingMessage);
                const firstId = missing[0];
                const el = document.querySelector(`.visit-item[data-item-id="${firstId}"]`);
                el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el?.querySelector('.photo-cam-btn')?.focus();
            } else if (!confirm('{{ __('visitors.submit_confirm') }}')) {
                e.preventDefault();
            }
        });
    }

    refreshCounts();
})().catch((e) => console.warn('Visit JS init failed', e));
</script>
@endpush
@endsection
