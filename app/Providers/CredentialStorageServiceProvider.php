<?php

namespace App\Providers;

use App\Services\CredentialStorageManager;
use Illuminate\Support\ServiceProvider;

class CredentialStorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CredentialStorageManager::class, function ($app) {
            return new CredentialStorageManager;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\AI\CredentialStorageStatus::class,
                \App\Console\Commands\AI\MigrateCredentialStorage::class,
            ]);
        }
    }
}
