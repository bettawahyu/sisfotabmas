<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('kelola-pengumuman', fn (User $user) => $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR));
        Gate::define('unduh-data', fn (User $user) => $user->hasRole(
            Role::ADMIN_LPPM, Role::OPERATOR, Role::KEUANGAN, Role::PIMPINAN, Role::PIMPINAN_UNIT,
        ));
    }
}
