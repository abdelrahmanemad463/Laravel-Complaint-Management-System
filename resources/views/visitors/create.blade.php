@extends('layouts.app')
@section('content')
<div class="mb-8">
    <a href="{{ route('visitors.home') }}" class="back-link">← {{ __('visitors.quality_visits') }}</a>
    <h1 class="page-title mt-4">{{ __('visitors.new_visit') }}</h1>
    <p class="page-subtitle">{{ __('visitors.choose_visit_type') }}</p>
</div>

<div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($visitTypes as $visitType)
    <a href="{{ route('visitors.setup', $visitType) }}" class="card group flex flex-col justify-between gap-6 transition duration-150 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md">
        <div><div class="text-lg font-bold text-slate-900">{{ $visitType->name }}</div>
        <p class="mt-2 text-sm text-slate-500">{{ $visitType->code }}</p></div>
        <span class="inline-flex w-fit items-center gap-1 rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{{ __('visitors.select') }} →</span>
    </a>
    @empty
    <div class="card col-span-full text-center text-slate-500">{{ __('visitors.no_visit_types') }}</div>
    @endforelse
</div>
@endsection
