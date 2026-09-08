@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('customers.index') }}" class="back-link">← {{ __('common.customers') }}</a>
        <h1 class="page-title mt-4">{{ $customer->name }}</h1>
        <p class="page-subtitle">{{ $customer->phone_primary }}</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        @can('customer.update')
            <a class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ route('customers.edit', $customer) }}">{{ __('common.edit') }}</a>
        @endcan
        @can('complaint.create')
            <a class="btn-primary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ route('complaints.create', ['customer' => $customer->id]) }}">{{ __('common.add_complaint') }}</a>
        @endcan
    </div>
</div>

<div class="grid gap-6 grid-cols-1 lg:grid-cols-3">
    <div class="card p-4 sm:p-6 lg:col-span-1">
        <h2 class="section-title">{{ __('common.customer_information') }}</h2>
        <dl class="mt-5 space-y-4 text-sm">
            <div><dt class="text-slate-500">{{ __('common.primary_phone') }}</dt><dd class="font-semibold">{{ $customer->phone_primary }}</dd></div>
            @foreach(['phone_2', 'phone_3', 'phone_4'] as $phone)
                <div><dt class="text-slate-500">{{ __('common.' . $phone) }}</dt><dd>{{ $customer->$phone ?: '—' }}</dd></div>
            @endforeach
            <div><dt class="text-slate-500">{{ __('common.address') }}</dt><dd>{{ $customer->address ?: '—' }}</dd></div>
        </dl>
    </div>
    <div class="card p-4 sm:p-6 lg:col-span-2">
        <h2 class="section-title">{{ __('common.complaint_summary') }}</h2>
        <div class="mt-5 grid grid-cols-1 xs:grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="stat"><b>{{ $customer->complaints->count() }}</b><span>{{ __('common.total') }}</span></div>
            @foreach(['Pending' => 'pending', 'In Progress' => 'in_progress', 'Solved' => 'solved', 'Closed' => 'closed'] as $status => $statusKey)
                <div class="stat"><b>{{ $customer->complaints->where('status.name_en', $status)->count() }}</b><span>{{ __('common.' . $statusKey) }}</span></div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mt-6 overflow-hidden p-0">
    <div class="flex items-center justify-between border-b px-6 py-4">
        <h2 class="section-title">{{ __('common.complaint_history') }}</h2>
        <a class="text-sm font-semibold text-indigo-700" href="{{ route('complaints.index', ['customer_id' => $customer->id]) }}">{{ __('common.view_complaints') }} →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>{{ __('common.complaint') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.priority') }}</th><th>{{ __('common.branch') }}</th><th>{{ __('common.date') }}</th></tr></thead>
            <tbody>
                @forelse($customer->complaints->sortByDesc('complaint_date') as $complaint)
                    <tr><td><a class="font-semibold text-indigo-700" href="{{ route('complaints.show', $complaint) }}">#{{ $complaint->id }} — {{ $complaint->short_description }}</a></td><td><span class="badge" style="--badge-color:{{ $complaint->status?->color }}">{{ $complaint->status?->localized_name }}</span></td><td><span class="badge" style="--badge-color:{{ $complaint->priority?->color }}">{{ $complaint->priority?->localized_name }}</span></td><td>{{ $complaint->branch?->localized_name }}</td><td>{{ optional($complaint->complaint_date)->format('Y-m-d') }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty">{{ __('common.no_complaints') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
