<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Services\IdentitasAplikasi;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(IdentitasAplikasi::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(IdentitasAplikasi::class)->terapkanKeConfig();

        Gate::define('kelola-pengumuman', fn (User $user) => $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR));
        Gate::define('unduh-data', fn (User $user) => $user->hasRole(
            Role::ADMIN_LPPM, Role::OPERATOR, Role::KEUANGAN, Role::PIMPINAN, Role::PIMPINAN_UNIT,
        ));
        Gate::define('lihat-data-dosen', fn (User $user) => $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR, Role::PIMPINAN_UNIT));
    }
}
