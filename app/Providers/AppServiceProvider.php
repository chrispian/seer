<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        // Configure global model protection - unguard all models by default
        // Individual models can still override with $guarded property if needed
        Model::unguard();

        // Enable strict model behavior in local development
        if (! app()->isProduction()) {
            // Prevent lazy loading to catch N+1 query problems early
            Model::preventLazyLoading();

            // Prevent silently discarding attributes to catch typos/issues
            Model::preventSilentlyDiscardingAttributes();

            // Prevent accessing missing attributes to catch issues early
            Model::preventAccessingMissingAttributes();
        }
    }
}
