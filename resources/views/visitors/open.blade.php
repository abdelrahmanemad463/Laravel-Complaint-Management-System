@extends('layouts.app')
@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div><a href="{{ route('visitors.home') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
    <h1 class="page-title mt-4">{{ __('visitors.open_visits') }}</h1>
    <p class="page-subtitle">{{ $visits->count() }} {{ __('visitors.open_visits') }}</p></div>
    @can('visit.create')<a href="{{ route('visitors.create') }}" class="btn-primary">{{ __('visitors.new_visit') }}</a>@endcan
</div>

@if($visits->count())
<div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($visits as $visit)
    <div class="card flex flex-col gap-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-bold text-slate-900">{{ $visit->branch?->name }}</div>
                <div class="text-sm text-slate-500">{{ $visit->visitType?->name }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $visit->visit_date?->format('Y-m-d') }}</div>
            </div>
            <span class="badge" style="--badge-color:#2563eb">{{ __('visitors.in_progress') }}</span>
        </div>
        <div>
            <div class="text-sm font-semibold text-slate-700">{{ $visit->reviewed_count }} / {{ $visit->items->count() }} {{ __('visitors.items_reviewed') }}</div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-indigo-600" style="width: {{ $visit->items->count() ? ($visit->reviewed_count / $visit->items->count()) * 100 : 0 }}%"></div>
            </div>
        </div>
        <div class="text-xs text-slate-500">{{ __('visitors.last_updated') }}: {{ $visit->updated_at?->diffForHumans() }}</div>
        <a href="{{ route('visitors.show', $visit) }}" class="btn-secondary w-full min-h-[44px] inline-flex items-center justify-center">{{ __('visitors.continue_visit') }}</a>
    </div>
    @endforeach
</div>
@else
<div class="card text-center text-slate-500">{{ __('visitors.no_open_visits') }}</div>
@endif
@endsection
