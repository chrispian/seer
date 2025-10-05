<?php

namespace App\Providers;

use App\Decorators\CommandTelemetryDecorator;
use App\Services\Commands\DSL\CommandRunner;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for TELEMETRY-004: Command & DSL Execution Metrics
 */
class CommandTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the command telemetry decorator when binding CommandRunner
        $this->app->extend(CommandRunner::class, function (CommandRunner $commandRunner, $app) {
            if (config('command-telemetry.enabled', true)) {
                return CommandTelemetryDecorator::wrap($commandRunner);
            }

            return $commandRunner;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/command-telemetry.php' => config_path('command-telemetry.php'),
            ], 'command-telemetry-config');
        }
    }
}
