<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Complaint Desk' }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight text-indigo-700">Complaint Desk</a>
            @auth
            <nav class="hidden items-center gap-4 text-sm font-medium lg:flex">
                <a href="{{ route('dashboard') }}" class="nav-link">{{ __('common.dashboard') }}</a>
                <a href="{{ route('customers.index') }}" class="nav-link">{{ __('common.customers') }}</a>
                <a href="{{ route('complaints.index') }}" class="nav-link">{{ __('common.complaints') }}</a>
                @can('report.view')<a href="{{ route('reports.branches') }}" class="nav-link">{{ __('common.reports') }}</a>@endcan
                @can('branch.view')<a href="{{ route('master.index','branches') }}" class="nav-link">{{ __('common.master_data') }}</a>@endcan
                @can('user.view')<a href="{{ route('users.index') }}" class="nav-link">{{ __('common.users') }}</a>@endcan
                @can('role.view')<a href="{{ route('roles.index') }}" class="nav-link">{{ __('common.roles') }}</a>@endcan
                @can('audit.view')<a href="{{ route('audit-logs.index') }}" class="nav-link">{{ __('common.audit_logs') }}</a>@endcan
            </nav>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('locale', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-md border px-2 py-1">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
                <span class="hidden text-slate-600 sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-rose-600 hover:underline">{{ __('common.logout') }}</button></form>
            </div>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8">
        @if(session('success'))<div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><p class="font-semibold">{{ __('common.fix_errors') }}</p><ul class="mt-1 list-disc ps-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</div>
</body>
</html>
