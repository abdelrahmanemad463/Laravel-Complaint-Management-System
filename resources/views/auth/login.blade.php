@extends('layouts.app')
@section('content')
<div class="mx-auto mt-12 max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-8"><p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Complaint Desk</p><h1 class="mt-2 text-2xl font-bold">{{ __('common.sign_in') }}</h1><p class="mt-2 text-sm text-slate-500">{{ __('common.sign_in_help') }}</p></div>
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">@csrf
        <div><label class="form-label">{{ __('common.email') }}</label><input name="email" type="email" value="{{ old('email') }}" required autofocus class="form-input"></div>
        <div><label class="form-label">{{ __('common.password') }}</label><input name="password" type="password" required class="form-input"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> {{ __('common.remember') }}</label>
        <button class="btn-primary w-full">{{ __('common.sign_in') }}</button>
    </form>
    <p class="mt-6 text-xs text-slate-500">Demo: admin@example.com / password</p>
</div>
@endsection
