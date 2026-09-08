@extends('layouts.app')

@section('content')
<div class="mb-8">
    <p class="eyebrow">{{ __('common.analytics') }}</p>
    <h1 class="page-title">{{ __('common.branch_reports') }}</h1>
    <p class="page-subtitle">{{ __('common.branch_reports_subtitle') }}</p>
</div>

<form method="GET" action="{{ route('reports.branches') }}" class="card mb-6 p-4 sm:p-6">
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="form-label">{{ __('common.date_from') }}</label>
            <input class="form-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label class="form-label">{{ __('common.date_to') }}</label>
            <input class="form-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
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
                    @forelse($branches as $branch)
                        <button type="button" class="branch-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $branch->id }}" data-name="{{ $branch->localized_name }}" data-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? '1' : '0' }}" role="option" aria-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? 'true' : 'false' }}">
                            <span class="min-w-0 truncate font-medium">{{ $branch->localized_name }}</span>
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-300 text-xs text-indigo-600"><span class="branch-check" aria-hidden="true">{{ in_array($branch->id, $selectedBranchIds, true) ? '✓' : '' }}</span></span>
                        </button>
                    @empty
                        <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_branch_matches') }}</p>
                    @endforelse
                </div>
                <button type="button" class="hidden w-full rounded-md px-3 py-2 text-start text-sm font-semibold text-indigo-700 hover:bg-indigo-50" data-picker-clear>{{ __('common.clear') }}</button>
            </div>
        </div>
    </div>

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <button class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.apply_filters') }}</button>
        <a class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ route('reports.branches') }}">{{ __('common.reset') }}</a>
    </div>
</form>

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-slate-500">{{ __('common.results_count', ['count' => $rows->total()]) }}</p>
</div>

<div class="card overflow-hidden p-0">
    <div class="overflow-x-auto -mx-3 sm:mx-0">
        <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('common.date') }}</th>
                <th>{{ __('common.branch') }}</th>
                <th>{{ __('common.complaints') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->complaint_date }}</td>
                    <td>{{ $row->branch?->localized_name }}</td>
                    <td class="font-semibold">{{ $row->total }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">{{ __('common.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="border-t border-slate-200 p-4">
        {{ $rows->links() }}
    </div>
</div>
@endsection
