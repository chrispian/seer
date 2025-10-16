<?php

namespace Modules\UiBuilder;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class UiBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module config
        $this->mergeConfigFrom(
            __DIR__ . '/config/ui-builder.php', 'ui-builder'
        );

        // Register model bindings - keep backward compatibility
        $this->app->bind(\App\Models\FeUiPage::class, \Modules\UiBuilder\Models\FeUiPage::class);
        $this->app->bind(\App\Models\FeUiComponent::class, \Modules\UiBuilder\Models\FeUiComponent::class);
        $this->app->bind(\App\Models\FeUiDatasource::class, \Modules\UiBuilder\Models\FeUiDatasource::class);
        $this->app->bind(\App\Models\FeUiAction::class, \Modules\UiBuilder\Models\FeUiAction::class);
        $this->app->bind(\App\Models\FeUiRegistry::class, \Modules\UiBuilder\Models\FeUiRegistry::class);
        $this->app->bind(\App\Models\FeUiModule::class, \Modules\UiBuilder\Models\FeUiModule::class);
        $this->app->bind(\App\Models\FeUiTheme::class, \Modules\UiBuilder\Models\FeUiTheme::class);
        $this->app->bind(\App\Models\FeUiFeatureFlag::class, \Modules\UiBuilder\Models\FeUiFeatureFlag::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        
        // Publish config
        $this->publishes([
            __DIR__ . '/config/ui-builder.php' => config_path('ui-builder.php'),
        ], 'ui-builder-config');

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register module commands here
            ]);
        }
    }
}