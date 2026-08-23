@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('complaints.index') }}" class="back-link">← {{ __('common.complaints') }}</a>
        <h1 class="page-title mt-4">#{{ $complaint->id }} {{ $complaint->short_description }}</h1>
        <p class="page-subtitle">{{ $complaint->customer?->name }} · {{ optional($complaint->complaint_date)->format('Y-m-d') }}</p>
    </div>
    @can('complaint.update')
        <a class="btn-primary" href="{{ route('complaints.edit', $complaint) }}">{{ __('common.edit_complaint') }}</a>
    @endcan
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="flex flex-wrap gap-2">
            <span class="badge" style="--badge-color:{{ $complaint->status?->color }}">{{ $complaint->status?->name }}</span>
            <span class="badge" style="--badge-color:{{ $complaint->priority?->color }}">{{ $complaint->priority?->name }}</span>
            <span class="badge" style="--badge-color:{{ $complaint->service?->color }}">{{ $complaint->service?->name }}</span>
        </div>
        <h2 class="section-title mt-6">{{ __('common.description') }}</h2>
        <p class="mt-3 whitespace-pre-line text-slate-700">{{ $complaint->description }}</p>
        @if($complaint->resolution)
            <h2 class="section-title mt-8">{{ __('common.resolution') }}</h2>
            <p class="mt-3 whitespace-pre-line text-slate-700">{{ $complaint->resolution }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $complaint->resolver?->name }} · {{ optional($complaint->resolved_at)->format('Y-m-d H:i') }}</p>
        @endif
    </div>
    <div class="card">
        <h2 class="section-title">{{ __('common.complaint_information') }}</h2>
        <dl class="mt-5 space-y-4 text-sm">
            <div><dt class="text-slate-500">{{ __('common.customer') }}</dt><dd><a class="font-semibold text-indigo-700" href="{{ route('customers.show', $complaint->customer) }}">{{ $complaint->customer?->name }}</a></dd></div>
            <div><dt class="text-slate-500">{{ __('common.branch') }}</dt><dd>{{ $complaint->branch?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('common.source') }}</dt><dd>{{ $complaint->source?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('common.category') }}</dt><dd>{{ $complaint->category?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('common.type') }}</dt><dd>{{ $complaint->type?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('common.created_by') }}</dt><dd>{{ $complaint->creator?->name }}</dd></div>
        </dl>
    </div>
</div>

<div class="card mt-6">
    <h2 class="section-title">{{ __('common.timeline') }}</h2>
    <div class="mt-6 space-y-6 border-s-2 border-slate-200 ps-6">
        @foreach($complaint->statusHistories as $event)
            <div class="relative">
                <span class="absolute -start-[31px] top-1 h-3 w-3 rounded-full bg-indigo-500"></span>
                <p class="text-xs text-slate-500">{{ optional($event->changed_at)->format('Y-m-d H:i') }}</p>
                <p class="mt-1 font-semibold">{{ $event->changer?->name }}: {{ $event->fromStatus?->name ?? __('common.created') }} → {{ $event->toStatus?->name }}</p>
                @if($event->reason)<p class="mt-1 text-sm text-slate-600">{{ $event->reason }}</p>@endif
            </div>
        @endforeach
        @foreach($complaint->activityLogs->sortBy('created_at') as $event)
            @php($activityTranslation = 'activity.' . $event->action)
            <div class="relative">
                <span class="absolute -start-[31px] top-1 h-3 w-3 rounded-full bg-slate-400"></span>
                <p class="text-xs text-slate-500">{{ optional($event->created_at)->format('Y-m-d H:i') }}</p>
                <p class="mt-1 font-semibold">{{ $event->user?->name ?? __('common.system') }}</p>
                <p class="text-sm text-slate-600">{{ \Illuminate\Support\Facades\Lang::has($activityTranslation) ? __($activityTranslation) : __('common.activity_recorded') }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
