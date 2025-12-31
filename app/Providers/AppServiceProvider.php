<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        $this->registerRoutes();

        Gate::before(function ($user) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
                ? true
                : null;
        });

        Gate::define('perm', function ($user, string $permissionSlug) {
            return method_exists($user, 'hasCompanyPermission')
                ? $user->hasCompanyPermission($permissionSlug)
                : false;
        });
    }


    protected function registerRoutes()
    {
        //
    }
}
