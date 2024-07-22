<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class WebFetcherProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('App\Services\WebFetcher', function () {
            return new \App\Services\WebFetcher();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
