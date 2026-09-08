@php($editing=isset($complaint))
@extends('layouts.app')
@section('content')
@php($selectedCustomer = $customer ?? ($complaint->customer ?? null))
@php($selectedCustomerId = old('customer_id', $selectedCustomer->id ?? ''))
<div class="mb-8"><a href="{{ $editing ? route('complaints.show',$complaint) : route('complaints.index') }}" class="back-link">← {{ __('common.back') }}</a><h1 class="page-title mt-4">{{ $editing ? __('common.edit_complaint') : __('common.new_complaint') }}</h1><p class="page-subtitle">{{ $customer->name ?? $complaint->customer->name ?? __('common.select_customer') }}</p></div>
<form method="POST" action="{{ $editing ? route('complaints.update',$complaint) : route('complaints.store') }}" class="card p-4 sm:p-6 space-y-6">@csrf @if($editing) @method('PUT') @endif
<div class="relative rounded-xl border border-indigo-100 bg-indigo-50/60 p-4" data-customer-picker data-filter-dropdown data-search-url="{{ route('customers.search') }}" data-empty-text="{{ __('common.no_customer_matches') }}" data-select-label="{{ __('common.select_customer') }}" data-singular-label="{{ __('common.customer') }}">
    <label class="form-label">{{ __('common.customer') }} *</label>
    <input type="hidden" name="customer_id" id="customer_id" value="{{ $selectedCustomerId }}" required>
    <button type="button" class="form-input mt-1 flex items-center justify-between gap-3 text-start" data-picker-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="customer-results">
        <span id="customer-summary" class="truncate {{ $selectedCustomer ? 'text-slate-900' : 'text-slate-500' }}">{{ $selectedCustomer ? $selectedCustomer->name.' — '.$selectedCustomer->phone_primary : __('common.select_customer') }}</span>
        <span class="shrink-0 text-slate-500" aria-hidden="true">▾</span>
    </button>
    <div id="customer-results" class="dropdown-panel absolute start-4 end-4 top-full z-30 mt-2 hidden overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-lg" role="listbox" aria-label="{{ __('common.customer') }}">
        <div class="border-b border-slate-200 p-1">
            <input type="search" id="customer-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_customers') }}" data-picker-search>
        </div>
        <div class="max-h-72 space-y-1 overflow-y-auto p-1" data-picker-options>
            @forelse($data['customers'] as $customerOption)
                <button type="button" class="customer-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $customerOption->id }}" data-name="{{ $customerOption->name }}" data-phone="{{ $customerOption->phone_primary }}" data-selected="{{ $selectedCustomerId == $customerOption->id ? '1' : '0' }}" role="option" aria-selected="{{ $selectedCustomerId == $customerOption->id ? 'true' : 'false' }}">
                    <span class="min-w-0 truncate font-medium">{{ $customerOption->name }}</span>
                    <span class="flex shrink-0 items-center gap-2 text-xs text-slate-500"><span>{{ $customerOption->phone_primary }}</span><span class="customer-check text-indigo-600" aria-hidden="true">{{ $selectedCustomerId == $customerOption->id ? '✓' : '' }}</span></span>
                </button>
            @empty
                <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_customer_matches') }}</p>
            @endforelse
        </div>
        <button type="button" class="hidden w-full rounded-md px-3 py-2 text-start text-sm font-semibold text-indigo-700 hover:bg-indigo-50" data-picker-clear>{{ __('common.clear') }}</button>
    </div>
    <p class="mt-2 text-xs text-slate-600">{{ __('common.customer_required_help') }}</p>
</div>
<div class="grid gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">@foreach(['branch_id'=>'branches','service_id'=>'services','source_id'=>'sources','status_id'=>'statuses'] as $field=>$key)<div><label class="form-label">{{ __('common.'.str_replace('_id','',$field)) }} *</label><select class="form-input" required name="{{ $field }}"><option value="">{{ __('common.select') }}</option>@foreach($data[$key] as $item)<option value="{{ $item->id }}" @selected(old($field,$complaint->$field??'')==$item->id)>{{ $item->localized_name }}</option>@endforeach</select></div>@endforeach<div><label class="form-label">{{ __('common.type') }} *</label><select class="form-input" required name="type_id" data-type-select><option value="">{{ __('common.select') }}</option>@foreach($data['types'] as $item)<option value="{{ $item->id }}" @selected(old('type_id',$complaint->type_id??'')==$item->id) data-category-id="{{ $item->category_id }}" data-category-name="{{ $item->category?->localized_name }}" data-category-color="{{ $item->category?->color }}" data-priority-id="{{ $item->priority_id }}" data-priority-name="{{ $item->priority?->localized_name }}" data-priority-color="{{ $item->priority?->color }}">{{ $item->localized_name }}</option>@endforeach</select></div><div><label class="form-label">{{ __('common.complaint_date') }} *</label><input class="form-input" type="date" required name="complaint_date" value="{{ old('complaint_date',optional($complaint->complaint_date ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"></div></div>
<div id="type-derived" class="hidden rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ __('common.auto_determined') }}</p>
    <div class="flex flex-wrap gap-2">
        <span id="derived-category" class="badge font-semibold" style="--badge-color:#94a3b8"><span class="opacity-70">{{ __('common.category') }}:</span> <b data-derived-category-name class="ms-1">—</b></span>
        <span id="derived-priority" class="badge font-semibold" style="--badge-color:#94a3b8"><span class="opacity-70">{{ __('common.priority') }}:</span> <b data-derived-priority-name class="ms-1">—</b></span>
    </div>
</div>
<div><label class="form-label">{{ __('common.short_description') }} *</label><input class="form-input" required name="short_description" value="{{ old('short_description',$complaint->short_description??'') }}"></div><div><label class="form-label">{{ __('common.description') }} *</label><textarea class="form-input" required rows="6" name="description">{{ old('description',$complaint->description??'') }}</textarea></div>
<div class="grid gap-5 grid-cols-1 sm:grid-cols-2"><div><label class="form-label">{{ __('common.serial_number') }}</label><input class="form-input" name="serial_number" value="{{ old('serial_number',$complaint->serial_number??'') }}"></div><div><label class="form-label">{{ __('common.price') }}</label><input class="form-input" type="number" step="0.01" min="0" name="price" value="{{ old('price',$complaint->price??'') }}"></div></div>
<div><label class="form-label">{{ __('common.resolution') }}</label><textarea class="form-input" rows="4" name="resolution">{{ old('resolution',$complaint->resolution??'') }}</textarea></div>@if($editing)<div><label class="form-label">{{ __('common.status_reason') }}</label><input class="form-input" name="status_reason" placeholder="{{ __('common.status_reason_hint') }}"></div>@endif
<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ $editing ? route('complaints.show',$complaint) : route('complaints.index') }}">{{ __('common.cancel') }}</a><button class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.save') }}</button></div>
</form>
<script>
    (() => {
        const select = document.querySelector('[data-type-select]');
        const panel = document.getElementById('type-derived');
        if (!select || !panel) return;
        const categoryName = panel.querySelector('[data-derived-category-name]');
        const priorityName = panel.querySelector('[data-derived-priority-name]');
        const categoryBadge = document.getElementById('derived-category');
        const priorityBadge = document.getElementById('derived-priority');
        const render = () => {
            const option = select.selectedOptions[0];
            const hasConfig = Boolean(option && option.dataset.categoryId && option.dataset.priorityId);
            categoryName.textContent = hasConfig ? option.dataset.categoryName : '—';
            priorityName.textContent = hasConfig ? option.dataset.priorityName : '—';
            categoryBadge && categoryBadge.style.setProperty('--badge-color', option && option.dataset.categoryColor ? option.dataset.categoryColor : '#94a3b8');
            priorityBadge && priorityBadge.style.setProperty('--badge-color', option && option.dataset.priorityColor ? option.dataset.priorityColor : '#94a3b8');
            panel.classList.toggle('hidden', !hasConfig);
        };
        select.addEventListener('change', render);
        render();
    })();
</script>
@endsection
