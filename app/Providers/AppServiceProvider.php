<?php

namespace App\Providers;

use App\Models\User;
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
        // $me untuk layout dan halaman yang menampilkan menu sesuai peran.
        // ponytail: fallback admin pertama hanya sampai autentikasi (modul Akmal) aktif,
        // samakan dengan fallback di IssueController/HistoryController.
        View::composer(['layouts.app', 'history.*', 'admin.*', 'account.*'], function ($view) {
            static $me;
            $me ??= auth()->user()
                ?? User::query()->where('peran', 'admin')->first()
                ?? new User(['nama' => 'Tamu', 'username' => 'tamu', 'peran' => 'operator']);
            $view->with('me', $me);
        });
    }
}
