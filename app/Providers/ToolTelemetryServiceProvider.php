<?php

namespace App\Providers;

use App\Http\Middleware\ToolTelemetryMiddleware;
use App\Services\Telemetry\ToolHealthMonitor;
use App\Services\Telemetry\ToolMetricsAnalyzer;
use App\Services\Telemetry\ToolTelemetry;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class ToolTelemetryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register tool telemetry services
        $this->app->singleton(ToolTelemetry::class);
        $this->app->singleton(ToolHealthMonitor::class);
        $this->app->singleton(ToolMetricsAnalyzer::class);
    }

    public function boot()
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/tool-telemetry.php' => config_path('tool-telemetry.php'),
            ], 'tool-telemetry-config');
        }

        // Register middleware alias
        $router = $this->app['router'];
        $router->aliasMiddleware('tool.telemetry', ToolTelemetryMiddleware::class);

        // Schedule health checks if enabled
        if (config('tool-telemetry.health.enabled', true)) {
            $this->scheduleHealthChecks();
        }
    }

    private function scheduleHealthChecks()
    {
        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        
        $interval = config('tool-telemetry.health.check_interval_minutes', 5);
        
        $schedule->call(function () {
            $monitor = app(ToolHealthMonitor::class);
            $monitor->checkAllTools();
        })->everyMinute()->name('tool-health-check');
    }
}