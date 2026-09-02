<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\VisitorVisit;
use App\Policies\VisitorVisitPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Reports section access: management roles and read-only report roles.
        Gate::define('visitors.reports', function ($user) {
            return $user->can('report.view') || $user->can('visit.manage');
        });

        Gate::define('dashboard.visitors', function ($user) {
            return $user->can('dashboard.visitors.view');
        });

        Gate::policy(VisitorVisit::class, VisitorVisitPolicy::class);
    }
}
