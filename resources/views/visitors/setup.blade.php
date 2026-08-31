@extends('layouts.app')
@section('content')
<div class="mb-8">
    <a href="{{ route('visitors.create') }}" class="back-link">← {{ __('visitors.new_visit') }}</a>
    <h1 class="page-title mt-4">{{ $visitType->name }}</h1>
    <p class="page-subtitle">{{ __('visitors.setup_help') }}</p>
</div>

<div class="card max-w-xl">
    <form method="POST" action="{{ route('visitors.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="visit_type_id" value="{{ $visitType->id }}">

        <div>
            <label class="form-label" for="branch_id">{{ __('common.branch') }} *</label>
            <select name="branch_id" id="branch_id" class="form-input" required>
                <option value="">{{ __('common.select') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            @error('branch_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label">{{ __('visitors.inspector') }}</label>
            <input type="text" class="form-input bg-slate-100" value="{{ auth()->user()->name }}" readonly disabled>
            <p class="mt-1 text-xs text-slate-500">{{ __('visitors.inspector_readonly_help') }}</p>
        </div>

        <div>
            <label class="form-label" for="visit_date">{{ __('visitors.visit_date') }} *</label>
            <input type="date" name="visit_date" id="visit_date" class="form-input" value="{{ old('visit_date', now()->format('Y-m-d')) }}" required>
            @error('visit_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full">{{ __('visitors.start_visit') }}</button>
    </form>
</div>
@endsection
