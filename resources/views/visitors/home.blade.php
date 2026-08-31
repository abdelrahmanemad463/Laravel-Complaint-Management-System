@extends('layouts.app')
@section('content')
<div class="mb-8"><p class="eyebrow">{{ __('common.quality_visits') }}</p><h1 class="page-title">{{ __('visitors.quality_visits') }}</h1><p class="page-subtitle">{{ __('visitors.home_subtitle') }}</p></div>

<div class="grid gap-6 sm:grid-cols-1 md:grid-cols-3">
    @can('visit.create')
    <a href="{{ route('visitors.create') }}" class="card group flex flex-col items-center justify-center gap-4 text-center transition duration-150 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl text-indigo-700">+</span>
        <div><div class="text-lg font-bold text-slate-900">{{ __('visitors.new_visit') }}</div>
        <p class="mt-1 text-sm text-slate-500">{{ __('visitors.new_visit_help') }}</p></div>
    </a>
    @endcan
    @can('visit.master.view')
    <a href="{{ route('visitors.master-data') }}" class="card group flex flex-col items-center justify-center gap-4 text-center transition duration-150 hover:-translate-y-1 hover:border-slate-300 hover:shadow-md">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-3xl text-slate-700">☰</span>
        <div><div class="text-lg font-bold text-slate-900">{{ __('visitors.master_nav') }}</div>
        <p class="mt-1 text-sm text-slate-500">{{ __('visitors.master_nav_help') }}</p></div>
    </a>
    @endcan
    <a href="{{ route('visitors.open') }}" class="card group flex flex-col items-center justify-center gap-4 text-center transition duration-150 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-3xl text-emerald-700">▤</span>
        <div><div class="text-lg font-bold text-slate-900">{{ __('visitors.open_visits') }}</div>
        <p class="mt-1 text-sm text-slate-500">{{ __('visitors.open_visits_help') }}</p></div>
    </a>
    @can('visitors.reports')
    <a href="{{ route('visitors.reports') }}" class="card group flex flex-col items-center justify-center gap-4 text-center transition duration-150 hover:-translate-y-1 hover:border-amber-300 hover:shadow-md">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-3xl text-amber-700">▥</span>
        <div><div class="text-lg font-bold text-slate-900">{{ __('common.reports') }}</div>
        <p class="mt-1 text-sm text-slate-500">{{ __('visitors.reports_help') }}</p></div>
    </a>
    @endcan
</div>
@endsection
