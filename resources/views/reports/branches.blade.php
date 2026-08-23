@extends('layouts.app')

@section('content')
<div class="mb-8">
    <p class="eyebrow">{{ __('common.analytics') }}</p>
    <h1 class="page-title">{{ __('common.branch_reports') }}</h1>
    <p class="page-subtitle">{{ __('common.branch_reports_subtitle') }}</p>
</div>

<form method="GET" action="{{ route('reports.branches') }}" class="card mb-6">
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="form-label">{{ __('common.date_from') }}</label>
            <input class="form-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label class="form-label">{{ __('common.date_to') }}</label>
            <input class="form-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div data-branch-picker data-search-url="{{ route('complaints.branches.search') }}" data-empty-text="{{ __('common.no_branch_matches') }}" data-selected-ids="{{ implode(',', $selectedBranchIds) }}">
            <label class="form-label">{{ __('common.branch') }}</label>
            <input type="search" id="branch-search" class="form-input" autocomplete="off" placeholder="{{ __('common.search_branches') }}">
            <div id="selected-branch-inputs"></div>
            <div id="branch-results" class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1">
                @forelse($branches as $branch)
                    <button type="button" class="branch-option flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50" data-id="{{ $branch->id }}" data-name="{{ $branch->name }}" data-selected="{{ in_array($branch->id, $selectedBranchIds, true) ? '1' : '0' }}" aria-pressed="{{ in_array($branch->id, $selectedBranchIds, true) ? 'true' : 'false' }}">
                        <span class="font-medium">{{ $branch->name }}</span>
                        <span class="branch-check text-indigo-600">{{ in_array($branch->id, $selectedBranchIds, true) ? '✓' : '' }}</span>
                    </button>
                @empty
                    <p class="px-3 py-3 text-sm text-slate-500">{{ __('common.no_branch_matches') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-5 flex gap-3">
        <button class="btn-primary">{{ __('common.apply_filters') }}</button>
        <a class="btn-secondary" href="{{ route('reports.branches') }}">{{ __('common.reset') }}</a>
    </div>
</form>

<div class="card overflow-hidden p-0">
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
                    <td>{{ $row->branch?->name }}</td>
                    <td class="font-semibold">{{ $row->total }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">{{ __('common.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
