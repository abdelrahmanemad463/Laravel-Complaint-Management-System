@extends('layouts.app')

@section('content')
<div class="mb-8">
    <a class="back-link" href="{{ route('roles.index') }}">← {{ __('common.roles') }}</a>
    <h1 class="page-title mt-4">{{ __('common.new_role') }}</h1>
</div>

<form method="POST" action="{{ route('roles.store') }}" class="card max-w-2xl space-y-5">
    @csrf
    <div>
        <label class="form-label">{{ __('common.role_name') }} *</label>
        <input class="form-input" name="name" required value="{{ old('name') }}" autofocus>
    </div>
    <p class="text-sm text-slate-500">{{ __('common.role_create_help') }}</p>
    <div class="flex justify-end gap-3">
        <a class="btn-secondary" href="{{ route('roles.index') }}">{{ __('common.cancel') }}</a>
        <button class="btn-primary">{{ __('common.save') }}</button>
    </div>
</form>
@endsection
