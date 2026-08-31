@extends('layouts.app')
@section('content')
@php
    $scoreColor = $score['color'];
    $colorClasses = [
        'blue'   => ['#2563eb', 'text-blue-700 bg-blue-100'],
        'green'  => ['#16a34a', 'text-emerald-700 bg-emerald-100'],
        'yellow' => ['#ca8a04', 'text-amber-700 bg-amber-100'],
        'red'    => ['#dc2626', 'text-rose-700 bg-rose-100'],
        'slate'  => ['#475569', 'text-slate-700 bg-slate-100'],
    ];
    [$scoreHex, $scoreClasses] = $colorClasses[$scoreColor] ?? $colorClasses['slate'];
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ $visit->isCompleted() ? route('visitors.home') : route('visitors.open') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
        <h1 class="page-title mt-4">{{ $visit->branch?->name }}</h1>
        <p class="page-subtitle">{{ $visit->visitType?->name }} • {{ $visit->visit_date?->format('Y-m-d') }}</p>
        <p class="page-subtitle mt-1">{{ __('visitors.inspector') }}: {{ $visit->inspector?->name }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-center shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.live_score') }}</div>
            <div class="mt-1 flex items-center justify-center gap-2">
                <span data-live-score-value class="text-3xl font-black" style="color:{{ $scoreHex }}">{{ $score['percentage'] !== null ? $score['percentage'].'%' : '—' }}</span>
            </div>
            <div data-live-final class="mt-1 text-xs font-semibold" style="color:{{ $scoreHex }}">{{ $score['final'] }} / {{ $score['available'] }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-center shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('visitors.progress') }}</div>
            <div id="visit-progress-text" class="mt-1 text-2xl font-black text-slate-900">{{ $visit->items->filter->isReviewed()->count() }} / {{ $visit->items->count() }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ __('visitors.items_reviewed') }}</div>
        </div>
        @unless($visit->isCompleted())
        <form method="POST" action="{{ route('visitors.submit', $visit) }}" onsubmit="return confirm('{{ __('visitors.submit_confirm') }}');">
            @csrf
            <button type="submit" class="btn-primary">{{ __('visitors.submit_visit') }}</button>
        </form>
        @else
        <span class="badge" style="--badge-color:#16a34a">{{ __('visitors.completed') }}</span>
        @endunless
    </div>
</div>

<div id="visit-autosave-status" class="mb-4 hidden text-sm font-semibold"></div>

@if($visit->isCompleted())
@php $scoreHex2 = $colorClasses[$scoreColor][0]; @endphp
<div class="card mb-6"><div class="flex items-center gap-3"><span class="h-4 w-4 rounded-full" style="background:{{ $scoreHex2 }}"></span><span class="font-semibold" style="color:{{ $scoreHex2 }}">{{ __('visitors.score_class', ['class' => __("visitors.score_class_$scoreColor")]) }}</span></div></div>
@endif

{{-- Section navigation pills --}}
<div id="section-tabs" class="mb-8 flex flex-wrap gap-2">
    @foreach($grouped as $sectionName => $sectionItems)
        <button type="button" class="section-tab rounded-full border px-4 py-2 text-sm font-semibold transition" data-target="section-{{ Str::slug($sectionName) }}">
            <span class="section-tab-name">{{ $sectionName }}</span>
            <span class="ml-1 text-xs opacity-70 section-tab-count">{{ $sectionItems->filter->isReviewed()->count() }}/{{ $sectionItems->count() }}</span>
        </button>
    @endforeach
</div>

{{-- Checklist items grouped by section --}}
<form id="visit-checklist">
@csrf
<div class="space-y-10">
    @foreach($grouped as $sectionName => $sectionItems)
    <section id="section-{{ Str::slug($sectionName) }}" class="visit-section">
        <h2 class="section-title mb-4 flex items-center justify-between">
            <span>{{ $sectionName }}</span>
            <span class="text-sm font-normal text-slate-500 section-head-count">{{ $sectionItems->filter->isReviewed()->count() }}/{{ $sectionItems->count() }}</span>
        </h2>
        <div class="space-y-4">
            @foreach($sectionItems->sortBy('sort_order') as $itemIndex => $item)
            @php
                $ncRequired = $item->requiresPhoto();
            @endphp
            <article class="card visit-item" data-status="{{ $item->status }}" data-visited="{{ $item->visited_at ? '1' : '0' }}" data-item-id="{{ $item->id }}">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span class="badge" style="--badge-color:#475569">{{ $item->item_code }}</span>
                    <span class="badge" style="--badge-color:{{ $item->severity === 'critical' ? '#dc2626' : ($item->severity === 'major' ? '#ea580c' : '#16a34a') }}">{{ ucfirst($item->severity) }}</span>
                    <span class="text-base font-semibold text-slate-900">{{ $item->item_title }}</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="status-btn status-ok rounded-lg px-4 py-2 text-sm font-semibold" data-value="ok" @disabled($visit->isCompleted())>{{ __('visitors.option_ok') }}</button>
                    <button type="button" class="status-btn status-nc rounded-lg px-4 py-2 text-sm font-semibold" data-value="nc" @disabled($visit->isCompleted())>{{ __('visitors.option_nc') }}</button>
                    <button type="button" class="status-btn status-na rounded-lg px-4 py-2 text-sm font-semibold" data-value="na" @disabled($visit->isCompleted())>{{ __('visitors.option_na') }}</button>
                </div>

                {{-- Non-compliant details panel --}}
                <div class="nc-panel {{ $item->status === 'nc' ? '' : 'hidden' }} mt-4 space-y-4 rounded-xl border border-rose-200 bg-rose-50 p-4">
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

                    @if($item->main_kitchen || $item->support_department || true)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">{{ __('visitors.main_kitchen') }}</label>
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" class="mk-input h-4 w-4 rounded border-slate-300" value="1" @checked($item->main_kitchen) @disabled($visit->isCompleted())>
                                {{ __('visitors.main_kitchen') }}
                            </label>
                        </div>
                        <div>
                            <label class="form-label" for="dept_{{ $item->id }}">{{ __('visitors.support_department') }}</label>
                            <select class="form-input dept-select" id="dept_{{ $item->id }}" @disabled($visit->isCompleted())>
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($supportDepartments as $dept)
                                <option value="{{ $dept }}" @selected($item->support_department === $dept)>{{ __("visitors.support_dept_$dept") }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="form-label">{{ __('visitors.evidence_photo') }} @if($ncRequired)<span class="text-rose-600">*</span>@endif</label>
                        <input type="file" class="photo-input form-input" accept="image/jpeg,image/png,image/webp" @if($visit->isCompleted()) disabled @endif>
                        <p class="mt-1 text-xs text-slate-500">{{ __('visitors.photo_help') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2 photo-list">
                            @foreach($item->photos as $photo)
                            <a href="{{ route('visitors.photos.serve', $photo) }}" target="_blank" class="text-xs font-semibold text-indigo-700 underline">{{ $photo->original_name }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="text-sm font-bold text-slate-900">{{ __('visitors.approved_actions') }}</div>
                        <dl class="mt-2 space-y-2 text-sm">
                            <div class="flex gap-2"><dt class="w-40 shrink-0 text-slate-500">{{ __('visitors.immediate_action') }}</dt><dd>{{ $item->immediate_action ?: '—' }}</dd></div>
                            <div class="flex gap-2"><dt class="w-40 shrink-0 text-slate-500">{{ __('visitors.corrective_action') }}</dt><dd>{{ $item->corrective_action ?: '—' }}</dd></div>
                            <div class="flex gap-2"><dt class="w-40 shrink-0 text-slate-500">{{ __('visitors.preventive_action') }}</dt><dd>{{ $item->preventive_action ?: '—' }}</dd></div>
                            <div class="flex gap-2"><dt class="w-40 shrink-0 text-slate-500">{{ __('visitors.responsible') }}</dt><dd>{{ $item->responsible ?: '—' }}</dd></div>
                            <div class="flex gap-2"><dt class="w-40 shrink-0 text-slate-500">{{ __('visitors.deadline') }}</dt><dd>{{ $item->deadline ?: '—' }}</dd></div>
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

@push('scripts')
<script>
(() => {
    if (document.querySelector('.visit-item[data-visited="1"]') === null && document.querySelectorAll('.visit-item').length === 0) return;

    const statusEl = document.getElementById('visit-autosave-status');

    const setStatus = (text, kind) => {
        statusEl.classList.remove('hidden');
        statusEl.textContent = text;
        statusEl.classList.remove('text-emerald-700', 'text-slate-500');
        statusEl.classList.add(kind === 'saved' ? 'text-emerald-700' : 'text-slate-500');
    };

    const sleep = (ms) => new Promise(r => setTimeout(r, ms));
    let saveTimer;
    let controller;

    const csrf = document.querySelector('input[name="_token"]')?.value;

    const refreshCounts = () => {
        const items = document.querySelectorAll('.visit-item');
        let reviewed = 0;
        items.forEach((el) => { if (el.dataset.visited === '1') reviewed++; });
        const total = items.length;
        const progressText = document.getElementById('visit-progress-text');
        if (progressText) progressText.textContent = `${reviewed} / ${total}`;
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
    };

    const applyScore = (score) => {
        if (!score) return;
        const scoreWrap = document.querySelector('#live-score-value');
        // score bubble is server-rendered with data; update text + color
        const valueEl = document.querySelector('[data-live-score-value]');
        const finalEl = document.querySelector('[data-live-final]');
        if (valueEl) valueEl.textContent = score.percentage !== null ? score.percentage + '%' : '—';
        if (finalEl) finalEl.textContent = `${score.final} / ${score.available}`;
        const colors = { blue: '#2563eb', green: '#16a34a', yellow: '#ca8a04', red: '#dc2626', slate: '#475569' };
        const color = colors[score.color] || '#475569';
        [valueEl, finalEl].forEach((el) => { if (el) el.style.color = color; });
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
            applyScore(data.score);
            setStatus('{{ __('visitors.saved') }}', 'saved');
            setTimeout(() => statusEl.classList.add('hidden'), 1800);
        } catch (e) {
            setStatus(e.message || '{{ __('visitors.save_failed') }}', 'saving');
        }
    };

    const collectNcPayload = (itemEl) => {
        const rc = itemEl.querySelector('.rc-select')?.value || '';
        const note = itemEl.querySelector('.note-input')?.value || '';
        const mk = itemEl.querySelector('.mk-input')?.checked ? '1' : '0';
        const dept = itemEl.querySelector('.dept-select')?.value || '';
        return { root_cause_id: rc, note, main_kitchen: mk, support_department: dept };
    };

    document.querySelectorAll('.visit-item').forEach((itemEl) => {
        const ncPanel = itemEl.querySelector('.nc-panel');
        const applyButtonStyles = () => {
            const current = itemEl.dataset.status;
            itemEl.querySelectorAll('.status-btn').forEach((btn) => {
                const active = btn.dataset.value === current;
                btn.classList.toggle('bg-emerald-600', active && current === 'ok');
                btn.classList.toggle('text-white', active && current === 'ok');
                btn.classList.toggle('bg-rose-600', active && current === 'nc');
                btn.classList.toggle('bg-slate-600', active && current === 'na');
                btn.classList.toggle('text-white', active && current !== 'ok');
                btn.classList.toggle('bg-slate-100', !active);
                btn.classList.toggle('text-slate-700', !active);
            });
        };
        applyButtonStyles();

        itemEl.querySelectorAll('.status-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const value = btn.dataset.value;
                itemEl.dataset.status = value;
                itemEl.querySelector('.status-btn').blur();
                ncPanel.classList.toggle('hidden', value !== 'nc');
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
                    link.className = 'text-xs font-semibold text-indigo-700 underline';
                    link.textContent = data.photo.original_name;
                    list.appendChild(link);
                    setStatus('{{ __('visitors.saved') }}', 'saved');
                    setTimeout(() => statusEl.classList.add('hidden'), 1800);
                } catch (e) {
                    setStatus(e.message || '{{ __('visitors.save_failed') }}', 'saving');
                    photoInput.value = '';
                }
            });
        }
    });

    // Section tab navigation
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

    refreshCounts();
})().catch((e) => console.warn('Visit JS init failed', e));
</script>
@endpush
@endsection
