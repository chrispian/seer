<?php

namespace App\Providers;

use App\Services\Search\HybridSearchManager;
use Illuminate\Support\ServiceProvider;

class HybridSearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('hybrid-search', function ($app) {
            return new HybridSearchManager($app);
        });

        $this->app->singleton(\App\Services\VectorCapabilityDetector::class);

        $this->app->alias('hybrid-search', \App\Contracts\HybridSearchInterface::class);
    }

    public function boot()
    {
        //
    }
}
