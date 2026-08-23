@php($selectedCustomerId = $filters['customer_id'] ?? '')
@php($selectedCustomer = $data['customers']->firstWhere('id', $selectedCustomerId))
@php($selectedBranchIds = array_map('intval', $filters['branch_ids'] ?? []))

<form method="GET" action="{{ route('complaints.index') }}" class="card mb-6 space-y-5">
    <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
        <div data-customer-picker data-search-url="{{ route('customers.search') }}" data-empty-text="{{ __('common.no_customer_matches') }}">
            <label class="form-label">{{ __('common.customer') }}</label>
            <input type="hidden" name="customer_id" id="customer_id" value="{{ $selectedCustomerId }}">
            <input type="search" id="customer-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_customers') }}">
            <p id="selected-customer" class="mt-2 text-sm font-semibold text-indigo-700 {{ $selectedCustomer ? '' : 'hidden' }}">{{ $selectedCustomer ? $selectedCustomer->name.' — '.$selectedCustomer->phone_primary : '' }}</p>
            <div id="customer-results" class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1">
                @forelse($data['customers'] as $customer)
                    <button type="button" class="customer-option flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone_primary }}">
                        <span class="font-medium">{{ $customer->name }}</span>
                        <span class="text-xs text-slate-500">{{ $customer->phone_primary }}</span>
                    </button>
                @empty
                    <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_customer_matches') }}</p>
                @endforelse
            </div>
        </div>

        <div data-branch-picker data-search-url="{{ route('complaints.branches.search') }}" data-empty-text="{{ __('common.no_branch_matches') }}" data-selected-ids="{{ implode(',', $selectedBranchIds) }}">
            <label class="form-label">{{ __('common.branch') }}</label>
            <input type="search" id="branch-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_branches') }}">
            <div id="selected-branch-inputs"></div>
            <div id="branch-results" class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1">
                @forelse($data['branches'] as $branch)
                    <button type="button" class="branch-option flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $branch->id }}" data-name="{{ $branch->name }}" data-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? '1' : '0' }}" aria-pressed="{{ in_array($branch->id, $selectedBranchIds, true) ? 'true' : 'false' }}">
                        <span class="font-medium">{{ $branch->name }}</span>
                        <span class="branch-check text-indigo-600">{{ in_array($branch->id, $selectedBranchIds, true) ? '✓' : '' }}</span>
                    </button>
                @empty
                    <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_branch_matches') }}</p>
                @endforelse
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
    </div>

    <div class="flex flex-wrap gap-3">
        <button class="btn-primary">{{ __('common.apply_filters') }}</button>
        <a class="btn-secondary" href="{{ route('complaints.index') }}">{{ __('common.reset') }}</a>
        <a class="btn-secondary" href="{{ route('complaints.export', request()->query()) }}">{{ __('common.export_excel') }}</a>
    </div>
</form>
