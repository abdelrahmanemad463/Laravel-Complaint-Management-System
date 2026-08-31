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
<body class="bg-slate-50 text-slate-900 overflow-x-hidden">
<div class="flex min-h-screen flex-col overflow-x-hidden">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-3 py-3 sm:px-4 sm:py-4">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                @auth
                <button type="button" data-mobile-menu-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Open navigation menu" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 lg:hidden">
                    <span data-mobile-menu-icon-open class="text-lg leading-none">☰</span>
                    <span data-mobile-menu-icon-close class="hidden text-lg leading-none">✕</span>
                </button>
                @endauth
                <a href="{{ route('dashboard') }}" class="shrink-0 truncate whitespace-nowrap text-lg font-bold tracking-tight text-indigo-700 sm:text-xl">Complaint Desk</a>
            </div>
            @auth
            <nav class="hidden shrink-0 items-center gap-1.5 text-sm font-medium lg:flex xl:gap-2">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">{{ __('common.dashboard') }}</a>
                <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}">{{ __('common.customers') }}</a>
                <details class="group relative" data-nav-dropdown>
                    <summary class="nav-link cursor-pointer list-none {{ (request()->routeIs('complaints.*') || request()->routeIs('visitors.*')) ? 'nav-link-active' : '' }}">{{ __('common.complaints') }}</summary>
                    <div class="absolute start-0 top-full z-20 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                        <a href="{{ route('complaints.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 {{ request()->routeIs('complaints.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">{{ __('common.customer_complaints') }}</a>
                        @can('visit.view')<a href="{{ route('visitors.home') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 {{ request()->routeIs('visitors.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">{{ __('common.quality_visits') }}</a>@endcan
                    </div>
                </details>
                @can('report.view')<a href="{{ route('reports.branches') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">{{ __('common.reports') }}</a>@endcan
                @can('branch.view')<a href="{{ route('master.index','branches') }}" class="nav-link {{ request()->routeIs('master.*') ? 'nav-link-active' : '' }}">{{ __('common.master_data') }}</a>@endcan
                @can('user.view')<a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">{{ __('common.users') }}</a>@endcan
                @can('role.view')<a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}">{{ __('common.roles') }}</a>@endcan
                @can('audit.view')<a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'nav-link-active' : '' }}">{{ __('common.audit_logs') }}</a>@endcan
            </nav>
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2 text-sm xl:gap-3">
                <a href="{{ route('locale', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="hidden shrink-0 whitespace-nowrap rounded-md border px-2 py-1 text-xs sm:inline-flex sm:text-sm">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
                <button type="button" class="theme-toggle inline-flex h-10 w-10 shrink-0 items-center justify-center whitespace-nowrap p-0 sm:h-auto sm:w-auto sm:px-2.5 sm:py-1.5" data-theme-toggle data-light-label="{{ __('common.light_mode') }}" data-dark-label="{{ __('common.dark_mode') }}" data-theme-switcher="{{ __('common.theme_switcher') }}" aria-pressed="false" aria-label="{{ __('common.theme_switcher') }}">
                    <span data-theme-icon aria-hidden="true">☾</span>
                    <span data-theme-label class="hidden sm:inline ms-1">{{ __('common.dark_mode') }}</span>
                </button>
                <span class="hidden shrink-0 whitespace-nowrap text-slate-600 lg:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="hidden shrink-0 sm:block">@csrf<button class="whitespace-nowrap text-sm text-rose-600 hover:underline">{{ __('common.logout') }}</button></form>
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 lg:hidden" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            @endauth
            @guest
            <div class="flex shrink-0 items-center gap-2 text-sm">
                <a href="{{ route('locale', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="shrink-0 whitespace-nowrap rounded-md border px-2 py-1">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
            </div>
            @endguest
        </div>
    </header>

    @auth
    {{-- Mobile Drawer --}}
    <div id="mobile-menu" data-mobile-menu class="fixed inset-0 z-50 hidden lg:hidden" aria-hidden="true">
        <div data-mobile-menu-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div data-mobile-menu-panel class="absolute inset-y-0 start-0 flex w-[88%] max-w-[360px] flex-col overflow-hidden bg-white shadow-2xl transition duration-300 ease-out -translate-x-full rtl:translate-x-full data-[open=true]:translate-x-0">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <a href="{{ route('dashboard') }}" class="truncate text-base font-bold text-indigo-700">Complaint Desk</a>
                <button type="button" data-mobile-menu-close aria-label="Close menu" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">✕</button>
            </div>
            <div class="flex-1 overflow-y-auto px-2 py-3">
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'mobile-nav-link-active' : '' }}">{{ __('common.dashboard') }}</a>
                    <a href="{{ route('customers.index') }}" class="mobile-nav-link {{ request()->routeIs('customers.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.customers') }}</a>

                    <a href="{{ route('complaints.index') }}" class="mobile-nav-link {{ request()->routeIs('complaints.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.customer_complaints') }}</a>

                    @can('visit.view')
                    <a href="{{ url('/visitors') }}" class="mobile-nav-link {{ request()->routeIs('visitors.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.quality_visits') }}</a>
                    @endcan

                    @can('report.view')<a href="{{ route('reports.branches') }}" class="mobile-nav-link {{ request()->routeIs('reports.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.reports') }}</a>@endcan
                    @can('branch.view')<a href="{{ route('master.index','branches') }}" class="mobile-nav-link {{ request()->routeIs('master.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.master_data') }}</a>@endcan
                    @can('user.view')<a href="{{ route('users.index') }}" class="mobile-nav-link {{ request()->routeIs('users.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.users') }}</a>@endcan
                    @can('role.view')<a href="{{ route('roles.index') }}" class="mobile-nav-link {{ request()->routeIs('roles.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.roles') }}</a>@endcan
                    @can('audit.view')<a href="{{ route('audit-logs.index') }}" class="mobile-nav-link {{ request()->routeIs('audit-logs.*') ? 'mobile-nav-link-active' : '' }}">{{ __('common.audit_logs') }}</a>@endcan
                </nav>

                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ auth()->user()->name }}</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <a href="{{ route('locale', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-white">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
                        <button type="button" class="theme-toggle text-xs" data-theme-toggle data-light-label="{{ __('common.light_mode') }}" data-dark-label="{{ __('common.dark_mode') }}" data-theme-switcher="{{ __('common.theme_switcher') }}">
                            <span data-theme-icon>☾</span><span data-theme-label>{{ __('common.dark_mode') }}</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="w-full rounded-lg bg-rose-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">{{ __('common.logout') }}</button></form>
                </div>
            </div>
        </div>
    </div>
    @endauth
    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-6 sm:px-4 sm:py-8">
        @if(session('success'))<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 sm:mb-6 sm:text-base">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 sm:mb-6 sm:text-base">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 sm:mb-6"><p class="font-semibold">{{ __('common.fix_errors') }}</p><ul class="mt-1 list-disc ps-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-4 gap-y-1 px-4 py-2 text-xs text-slate-600 sm:justify-between">
            <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                <span class="font-semibold text-indigo-600">{{ __('common.footer_contact') }}:</span>
                <span class="font-semibold text-slate-900">{{ __('common.footer_name') }}</span>
                <a class="text-indigo-700 hover:underline" href="tel:{{ __('common.footer_phone_value') }}">{{ __('common.footer_phone') }}: {{ __('common.footer_phone_value') }}</a>
                <span class="text-slate-400" aria-hidden="true">•</span>
                <span class="font-semibold text-indigo-600">{{ __('common.footer_social') }}:</span>
                <a class="text-indigo-700 hover:underline" href="https://www.linkedin.com/in/abdelrahman-emad1" target="_blank" rel="noopener noreferrer">{{ __('common.footer_linkedin') }}</a>
                <a class="text-indigo-700 hover:underline" href="https://www.facebook.com/abdelrahman.emad.660867/" target="_blank" rel="noopener noreferrer">{{ __('common.footer_facebook') }}</a>
            </div>
            <p class="text-center text-slate-500">{{ __('common.footer_copyright', ['year' => now()->year]) }}</p>
        </div>
    </footer>
</div>
@stack('scripts')
<script>
    (() => {
        const dropdown = document.querySelector('[data-nav-dropdown]');
        if (dropdown) {
            dropdown.open = false;
            document.addEventListener('click', (e) => { if (!dropdown.contains(e.target)) dropdown.open = false; });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') dropdown.open = false; });
            dropdown.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => { dropdown.open = false; }));
        }
        const menu = document.querySelector('[data-mobile-menu]');
        const toggle = document.querySelector('[data-mobile-menu-toggle]');
        const overlay = document.querySelector('[data-mobile-menu-overlay]');
        const panel = document.querySelector('[data-mobile-menu-panel]');
        const closeBtn = document.querySelector('[data-mobile-menu-close]');
        if (!menu || !toggle || !panel) return;
        const iconOpen = toggle.querySelector('[data-mobile-menu-icon-open]');
        const iconClose = toggle.querySelector('[data-mobile-menu-icon-close]');
        const setOpen = (open) => {
            menu.classList.toggle('hidden', !open);
            menu.setAttribute('aria-hidden', open ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.setAttribute('data-open', open ? 'true' : 'false');
            if (iconOpen) iconOpen.classList.toggle('hidden', open);
            if (iconClose) iconClose.classList.toggle('hidden', !open);
            document.body.classList.toggle('overflow-hidden', open);
            if (open) panel.focus?.();
        };
        toggle.addEventListener('click', () => {
            const willOpen = menu.classList.contains('hidden');
            setOpen(willOpen);
        });
        closeBtn?.addEventListener('click', () => setOpen(false));
        overlay?.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.classList.contains('hidden')) setOpen(false); });
        panel.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
        // Close on resize to desktop
        const mql = window.matchMedia('(min-width: 1024px)');
        const onResize = () => { if (mql.matches) setOpen(false); };
        mql.addEventListener?.('change', onResize);
    })();
</script>
</body>
</html>
