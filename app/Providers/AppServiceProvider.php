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
        // $me untuk layout dan halaman yang menampilkan menu sesuai peran (lihat User::current()).
        View::composer(['layouts.app', 'history.*', 'admin.*', 'account.*'], function ($view) {
            static $me;
            $me ??= User::current() ?? new User(['nama' => 'Tamu', 'username' => 'tamu', 'peran' => 'operator']);
            $view->with('me', $me);
        });

        // Gerbang peran admin untuk rute /admin dan pembatalan.
        Gate::define('admin', fn (User $user) => $user->isAdmin());
    }
}
