<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate для админов
        Gate::define('admin-only', function ($user) {
            return $user->is_admin == true;
        });

        // Gate для поставщиков
        Gate::define('supplier-only', function ($user) {
            return $user->is_supplier == true && !$user->is_admin;
        });

        // Gate для главного админа
        Gate::define('main-admin', function ($user) {
            return $user->is_admin == true && $user->id === 1;
        });
    }
}
