<?php

namespace App\Providers;

use App\Models\User;
use App\Services\LayoutConfig;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LayoutConfig::class, fn (): LayoutConfig => LayoutConfig::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $me untuk layout dan halaman yang menampilkan menu sesuai peran. Rute ini selalu di balik auth.
        View::composer(['layouts.app', 'history.*', 'admin.*', 'account.*'], fn ($view) => $view->with('me', auth()->user()));

        // Kolom email muncul di tampilan begitu migrasi users.email (modul Akmal) sudah dijalankan.
        View::composer(['admin.users.*', 'account.*'], fn ($view) => $view->with(
            'emailSiap', \Illuminate\Support\Facades\Schema::hasColumn('users', 'email'),
        ));

        // Gerbang peran admin untuk rute /admin dan pembatalan.
        Gate::define('admin', fn (User $user) => $user->isAdmin());
    }
}
