<?php

namespace Modules\UiBuilder;

use Illuminate\Support\ServiceProvider;

class UiBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module config
        $this->mergeConfigFrom(
            __DIR__ . '/config/ui-builder.php', 'ui-builder'
        );

        // Register model bindings for backward compatibility
        $this->app->bind(\App\Models\FeUiPage::class, \Modules\UiBuilder\app\Models\Page::class);
        $this->app->bind(\App\Models\FeUiComponent::class, \Modules\UiBuilder\app\Models\Component::class);
        $this->app->bind(\App\Models\FeUiDatasource::class, \Modules\UiBuilder\app\Models\Datasource::class);
        $this->app->bind(\App\Models\FeUiAction::class, \Modules\UiBuilder\app\Models\Action::class);
        $this->app->bind(\App\Models\FeUiRegistry::class, \Modules\UiBuilder\app\Models\Registry::class);
        $this->app->bind(\App\Models\FeUiModule::class, \Modules\UiBuilder\app\Models\Module::class);
        $this->app->bind(\App\Models\FeUiTheme::class, \Modules\UiBuilder\app\Models\Theme::class);
        $this->app->bind(\App\Models\FeUiFeatureFlag::class, \Modules\UiBuilder\app\Models\FeatureFlag::class);
    }

    public function boot(): void
    {
        // Load migrations from database/migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        
        // Publish config
        $this->publishes([
            __DIR__ . '/config/ui-builder.php' => config_path('ui-builder.php'),
        ], 'ui-builder-config');
    }
}
