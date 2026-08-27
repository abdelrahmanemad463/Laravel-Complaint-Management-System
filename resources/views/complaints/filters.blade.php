@php($selectedCustomerId = $filters['customer_id'] ?? '')
@php($selectedCustomer = $data['customers']->firstWhere('id', $selectedCustomerId))
@php($selectedBranchIds = array_map('intval', $filters['branch_ids'] ?? []))

<form method="GET" action="{{ route('complaints.index') }}" class="card mb-6 space-y-5">
    <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
        <div>
            <label class="form-label">{{ __('common.complaint_id') }}</label>
            <input class="form-input" type="number" min="1" name="complaint_id" value="{{ $filters['complaint_id'] ?? '' }}" placeholder="#123">
        </div>


        <div class="relative" data-customer-picker data-filter-dropdown data-search-url="{{ route('customers.search') }}" data-empty-text="{{ __('common.no_customer_matches') }}" data-select-label="{{ __('common.select_customer') }}" data-singular-label="{{ __('common.customer') }}">
            <label class="form-label">{{ __('common.customer') }}</label>
            <input type="hidden" name="customer_id" id="customer_id" value="{{ $selectedCustomerId }}">
            <button type="button" class="form-input flex items-center justify-between gap-3 text-start" data-picker-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="customer-results">
                <span id="customer-summary" class="truncate {{ $selectedCustomer ? 'text-slate-900' : 'text-slate-500' }}">{{ $selectedCustomer ? $selectedCustomer->name.' — '.$selectedCustomer->phone_primary : __('common.select_customer') }}</span>
                <span class="shrink-0 text-slate-500" aria-hidden="true">▾</span>
            </button>
            <div id="customer-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden w-full min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-lg" role="listbox" aria-label="{{ __('common.customer') }}">
                <div class="border-b border-slate-200 p-1">
                    <input type="search" id="customer-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_customers') }}" data-picker-search>
                </div>
                <div class="max-h-56 space-y-1 overflow-y-auto p-1" data-picker-options>
                    @forelse($data['customers'] as $customer)
                        <button type="button" class="customer-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone_primary }}" data-selected="{{ $selectedCustomerId == $customer->id ? '1' : '0' }}" role="option" aria-selected="{{ $selectedCustomerId == $customer->id ? 'true' : 'false' }}">
                            <span class="min-w-0 truncate font-medium">{{ $customer->name }}</span>
                            <span class="flex shrink-0 items-center gap-2 text-xs text-slate-500"><span>{{ $customer->phone_primary }}</span><span class="customer-check text-indigo-600" aria-hidden="true">{{ $selectedCustomerId == $customer->id ? '✓' : '' }}</span></span>
                        </button>
                    @empty
                        <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_customer_matches') }}</p>
                    @endforelse
                </div>
                <button type="button" class="hidden w-full rounded-md px-3 py-2 text-start text-sm font-semibold text-indigo-700 hover:bg-indigo-50" data-picker-clear>{{ __('common.clear') }}</button>
            </div>
        </div>

        <div class="relative" data-branch-picker data-filter-dropdown data-search-url="{{ route('complaints.branches.search') }}" data-empty-text="{{ __('common.no_branch_matches') }}" data-selected-ids="{{ implode(',', $selectedBranchIds) }}" data-select-label="{{ __('common.select') }}" data-singular-label="{{ __('common.branch') }}" data-plural-label="{{ __('common.branches') }}">
            <label class="form-label">{{ __('common.branch') }}</label>
            <div id="selected-branch-inputs"></div>
            <button type="button" class="form-input flex items-center justify-between gap-3 text-start" data-picker-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="branch-results">
                <span id="branch-summary" class="truncate text-slate-500">{{ __('common.select') }}</span>
                <span class="shrink-0 text-slate-500" aria-hidden="true">▾</span>
            </button>
            <div id="branch-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden w-full min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-lg" role="listbox" aria-multiselectable="true" aria-label="{{ __('common.branch') }}">
                <div class="border-b border-slate-200 p-1">
                    <input type="search" id="branch-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_branches') }}" data-picker-search>
                </div>
                <div class="max-h-56 space-y-1 overflow-y-auto p-1" data-picker-options>
                    @forelse($data['branches'] as $branch)
                        <button type="button" class="branch-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $branch->id }}" data-name="{{ $branch->name }}" data-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? '1' : '0' }}" aria-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? 'true' : 'false' }}">
                            <span class="min-w-0 truncate font-medium">{{ $branch->name }}</span>
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-300 text-xs text-indigo-600"><span class="branch-check" aria-hidden="true">{{ in_array($branch->id, $selectedBranchIds, true) ? '✓' : '' }}</span></span>
                        </button>
                    @empty
                        <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_branch_matches') }}</p>
                    @endforelse
                </div>
                <button type="button" class="hidden w-full rounded-md px-3 py-2 text-start text-sm font-semibold text-indigo-700 hover:bg-indigo-50" data-picker-clear>{{ __('common.clear') }}</button>
            </div>
        </div>

        @foreach(['services'=>'service_id','sources'=>'source_id','categories'=>'category_id','types'=>'type_id','priorities'=>'priority_id','statuses'=>'status_id'] as $key => $field)
            <div>
                <label class="form-label">{{ __('common.'.$key) }}</label>
                <select class="form-input" name="{{ $field }}">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($data[$key] as $item)
                        <option value="{{ $item->id }}" @selected(($filters[$field] ?? null) == $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <div>
            <label class="form-label">{{ __('common.date_from') }}</label>
            <input class="form-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label class="form-label">{{ __('common.date_to') }}</label>
            <input class="form-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div>
            <label class="form-label">{{ __('common.search_complaint_descriptions') }}</label>
            <input class="form-input" type="search" name="description" value="{{ $filters['description'] ?? '' }}" placeholder="{{ __('common.search_complaint_descriptions') }}" autocomplete="off">
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button class="btn-primary">{{ __('common.apply_filters') }}</button>
        <a class="btn-secondary" href="{{ route('complaints.index') }}">{{ __('common.reset') }}</a>
        <a class="btn-secondary" href="{{ route('complaints.export', request()->query()) }}">{{ __('common.export_excel') }}</a>
    </div>
</form>
