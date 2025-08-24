<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Broadcast;
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
        
        // Alternative: You can set specific protection if needed in the future
        // Model::preventLazyLoading(! app()->isProduction());
        // Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
    }
}
