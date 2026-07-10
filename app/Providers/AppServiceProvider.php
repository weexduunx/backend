<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\WordSyncService::class, function ($app) {
            return new \App\Services\WordSyncService();
        });

        $this->app->singleton(\App\Services\EnhancedWordSyncService::class, function ($app) {
            return new \App\Services\EnhancedWordSyncService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
