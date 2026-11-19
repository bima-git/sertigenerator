<?php

namespace App\Providers;

use App\Models\User;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $me untuk layout dan halaman yang menampilkan menu sesuai peran. Rute ini selalu di balik auth.
        View::composer(['layouts.app', 'history.*', 'admin.*', 'account.*'], fn ($view) => $view->with('me', auth()->user()));

        // Gerbang peran admin untuk rute /admin dan pembatalan.
        Gate::define('admin', fn (User $user) => $user->isAdmin());
    }
}
