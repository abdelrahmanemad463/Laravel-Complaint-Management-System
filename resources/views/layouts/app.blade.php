<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5" data-theme-color>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Complaint Desk">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192x192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-180x180.png') }}">
    <title>{{ $title ?? 'Complaint Desk' }}</title>
    <script>
        (() => {
            const savedTheme = window.localStorage.getItem('complaint-theme');
            const theme = savedTheme === 'dark' || savedTheme === 'light' ? savedTheme : 'light';
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight text-indigo-700">Complaint Desk</a>
            @auth
            <nav class="hidden items-center gap-4 text-sm font-medium lg:flex">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">{{ __('common.dashboard') }}</a>
                <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}">{{ __('common.customers') }}</a>
                <a href="{{ route('complaints.index') }}" class="nav-link {{ request()->routeIs('complaints.*') ? 'nav-link-active' : '' }}">{{ __('common.complaints') }}</a>
                @can('report.view')<a href="{{ route('reports.branches') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">{{ __('common.reports') }}</a>@endcan
                @can('branch.view')<a href="{{ route('master.index','branches') }}" class="nav-link {{ request()->routeIs('master.*') ? 'nav-link-active' : '' }}">{{ __('common.master_data') }}</a>@endcan
                @can('user.view')<a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">{{ __('common.users') }}</a>@endcan
                @can('role.view')<a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}">{{ __('common.roles') }}</a>@endcan
                @can('audit.view')<a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'nav-link-active' : '' }}">{{ __('common.audit_logs') }}</a>@endcan
            </nav>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('locale', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-md border px-2 py-1">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
                <button type="button" class="theme-toggle" data-theme-toggle data-light-label="{{ __('common.light_mode') }}" data-dark-label="{{ __('common.dark_mode') }}" data-theme-switcher="{{ __('common.theme_switcher') }}" aria-pressed="false" aria-label="{{ __('common.theme_switcher') }}">
                    <span data-theme-icon aria-hidden="true">☾</span>
                    <span data-theme-label>{{ __('common.dark_mode') }}</span>
                </button>
                <span class="hidden text-slate-600 sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-rose-600 hover:underline">{{ __('common.logout') }}</button></form>
            </div>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8">
        @if(session('success'))<div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><p class="font-semibold">{{ __('common.fix_errors') }}</p><ul class="mt-1 list-disc ps-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</div>
</body>
</html>
