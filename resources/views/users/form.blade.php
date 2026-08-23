@php($editing=isset($user))
@extends('layouts.app')
@section('content')
<div class="mb-8"><a class="back-link" href="{{ route('users.index') }}">← {{ __('common.users') }}</a><h1 class="page-title mt-4">{{ $editing ? __('common.edit_user') : __('common.new_user') }}</h1></div>
<form method="POST" action="{{ $editing ? route('users.update',$user) : route('users.store') }}" class="card max-w-2xl space-y-5">@csrf @if($editing) @method('PUT') @endif
<div><label class="form-label">{{ __('common.name') }} *</label><input class="form-input" name="name" required value="{{ old('name',$user->name??'') }}"></div><div><label class="form-label">{{ __('common.email') }} *</label><input class="form-input" type="email" name="email" required value="{{ old('email',$user->email??'') }}"></div><div><label class="form-label">{{ __('common.password') }} {{ $editing ? '' : '*' }}</label><input class="form-input" type="password" name="password" {{ $editing ? '' : 'required' }}><p class="mt-1 text-xs text-slate-500">{{ $editing ? __('common.password_optional') : __('common.password_minimum') }}</p></div><div><label class="form-label">{{ __('common.role') }} *</label><select class="form-input" name="role" required>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(old('role',$user->roles->first()->name??'')===$role->name)>{{ $role->name }}</option>@endforeach</select></div><div class="flex justify-end gap-3"><a class="btn-secondary" href="{{ route('users.index') }}">{{ __('common.cancel') }}</a><button class="btn-primary">{{ __('common.save') }}</button></div>
</form>
@endsection
