<?php

namespace App\Providers;

use App\Servers\OrchestrationServer;
use HollisLabs\ToolCrate\Servers\ToolCrateServer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the SimpleTypeResolver for v2 UI system
        $this->app->bind(
            \App\Services\Types\TypeResolver::class,
            \App\Services\Types\SimpleTypeResolver::class
        );
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

        // Register internal tool routes
        Route::middleware(['api', 'tool.telemetry'])
            ->prefix('api/internal')
            ->group(base_path('routes/internal.php'));

        // Register MCP servers
        Mcp::local('laravel-tool-crate', ToolCrateServer::class);
        Mcp::local('orchestration', OrchestrationServer::class);
    }
}
