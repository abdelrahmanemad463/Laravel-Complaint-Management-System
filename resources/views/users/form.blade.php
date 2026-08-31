@extends('layouts.app')

@section('content')
@php($editing = isset($user))
@php($formUser = $user ?? new \App\Models\User())

<div class="mb-8">
    <a class="back-link" href="{{ route('users.index') }}">← {{ __('common.users') }}</a>
    <h1 class="page-title mt-4">{{ $editing ? __('common.edit_user') : __('common.new_user') }}</h1>
</div>

<form method="POST" action="{{ $editing ? route('users.update', $formUser) : route('users.store') }}" class="card max-w-2xl mx-auto p-4 sm:p-6 space-y-5">
    @csrf

    @if($editing)
        @method('PUT')
    @endif

    <div>
        <label class="form-label">{{ __('common.name') }} *</label>
        <input class="form-input" name="name" required value="{{ old('name', $formUser->name ?? '') }}">
    </div>

    <div>
        <label class="form-label">{{ __('common.email') }} *</label>
        <input class="form-input" type="email" name="email" required value="{{ old('email', $formUser->email ?? '') }}">
    </div>

    <div>
        <label class="form-label">{{ __('common.password') }} {{ $editing ? '' : '*' }}</label>
        <input class="form-input" type="password" name="password" @required(!$editing)>
        <p class="mt-1 text-xs text-slate-500">{{ $editing ? __('common.password_optional') : __('common.password_minimum') }}</p>
    </div>

    <div>
        <label class="form-label">{{ __('common.role') }} *</label>
        <select class="form-input" name="role" required>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', $formUser->roles->first()->name ?? '') === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a class="btn-secondary min-h-[44px] inline-flex items-center justify-center w-full sm:w-auto" href="{{ route('users.index') }}">{{ __('common.cancel') }}</a>
        <button class="btn-primary min-h-[44px] w-full sm:w-auto">{{ __('common.save') }}</button>
    </div>
</form>
@endsection
