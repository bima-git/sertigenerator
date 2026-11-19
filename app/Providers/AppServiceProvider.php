<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\LayoutConfig;
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
        //
    }
}
