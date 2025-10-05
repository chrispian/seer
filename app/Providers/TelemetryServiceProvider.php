<?php

namespace App\Providers;

use App\Console\Commands\TelemetryCleanupCommand;
use App\Console\Commands\TelemetryExportCommand;
use App\Console\Commands\TelemetryHealthCommand;
use App\Console\Commands\TelemetryQueryCommand;
use App\Console\Commands\TelemetryTestCommand;
use App\Services\Telemetry\TelemetryAdapter;
use App\Services\Telemetry\TelemetryQueryService;
use App\Services\Telemetry\TelemetrySink;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class TelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the telemetry configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/telemetry.php', 'telemetry'
        );

        // Register core telemetry services
        $this->app->singleton(TelemetrySink::class, function ($app) {
            return new TelemetrySink;
        });

        $this->app->singleton(TelemetryQueryService::class, function ($app) {
            return new TelemetryQueryService;
        });

        $this->app->singleton(TelemetryAdapter::class, function ($app) {
            return new TelemetryAdapter($app->make(TelemetrySink::class));
        });

        // Register console commands
        $this->commands([
            TelemetryQueryCommand::class,
            TelemetryCleanupCommand::class,
            TelemetryExportCommand::class,
            TelemetryHealthCommand::class,
            TelemetryTestCommand::class,
        ]);
    }

    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/telemetry.php' => config_path('telemetry.php'),
            ], 'telemetry-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../../database/migrations/2024_10_04_create_telemetry_tables.php' => database_path('migrations/2024_10_04_create_telemetry_tables.php'),
            ], 'telemetry-migrations');
        }

        // Schedule telemetry maintenance tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Schedule automatic cleanup
            if (config('telemetry.enabled', true)) {
                $schedule->command('telemetry:cleanup --force')
                    ->daily()
                    ->at('02:00')
                    ->description('Clean up old telemetry data');

                // Flush telemetry buffers periodically
                $schedule->call(function () {
                    app(TelemetrySink::class)->flush();
                })
                    ->everyMinute()
                    ->description('Flush telemetry buffers');

                // System health check
                $schedule->command('telemetry:health --format=json')
                    ->everyFiveMinutes()
                    ->description('Check system health');
            }
        });

        // Register shutdown handler to flush buffers
        if (config('telemetry.enabled', true)) {
            register_shutdown_function(function () {
                try {
                    app(TelemetrySink::class)->flush();
                } catch (\Exception $e) {
                    // Silently fail to avoid breaking application shutdown
                }
            });
        }

        // Set up telemetry event listeners
        $this->setupEventListeners();
    }

    /**
     * Set up event listeners to capture telemetry data from existing systems
     */
    private function setupEventListeners(): void
    {
        if (! config('telemetry.enabled', true)) {
            return;
        }

        $adapter = $this->app->make(TelemetryAdapter::class);

        // Listen for log events and capture telemetry data
        $this->app['log']->listen(function ($level, $message, $context) use ($adapter) {
            // Only capture telemetry from our telemetry channels
            if (isset($context['channel']) && $this->isTelemetryChannel($context['channel'])) {
                $this->captureTelemetryFromLog($adapter, $level, $message, $context);
            }
        });
    }

    /**
     * Check if log channel is a telemetry channel
     */
    private function isTelemetryChannel(string $channel): bool
    {
        return in_array($channel, [
            'tool-telemetry',
            'command-telemetry',
            'fragment-processing-telemetry',
            'chat-telemetry',
        ]);
    }

    /**
     * Capture telemetry data from log entries
     */
    private function captureTelemetryFromLog(TelemetryAdapter $adapter, string $level, string $message, array $context): void
    {
        try {
            // Parse channel to determine event type
            $eventType = $this->parseEventTypeFromChannel($context['channel'] ?? '');

            // Extract event data from context
            $eventData = $this->extractEventDataFromContext($context);
            $eventData['level'] = $level;
            $eventData['message'] = $message;

            // Route to appropriate adapter method
            match ($eventType) {
                'tool' => $adapter->adaptToolEvent($eventData['event_name'] ?? 'log_event', $eventData),
                'command' => $adapter->adaptCommandEvent($eventData['event_name'] ?? 'log_event', $eventData),
                'fragment' => $adapter->adaptFragmentEvent($eventData['event_name'] ?? 'log_event', $eventData),
                'chat' => $adapter->adaptChatEvent($eventData['event_name'] ?? 'log_event', $eventData),
                default => null // Ignore unknown types
            };

        } catch (\Exception $e) {
            // Silently fail to avoid breaking the application
            // In production, you might want to log this to a separate error channel
        }
    }

    /**
     * Parse event type from log channel name
     */
    private function parseEventTypeFromChannel(string $channel): string
    {
        if (str_contains($channel, 'tool')) {
            return 'tool';
        }
        if (str_contains($channel, 'command')) {
            return 'command';
        }
        if (str_contains($channel, 'fragment')) {
            return 'fragment';
        }
        if (str_contains($channel, 'chat')) {
            return 'chat';
        }

        return 'unknown';
    }

    /**
     * Extract event data from log context
     */
    private function extractEventDataFromContext(array $context): array
    {
        return [
            'correlation_id' => $context['correlation_id'] ?? null,
            'event_name' => $context['event'] ?? $context['event_name'] ?? 'log_event',
            'tool_name' => $context['tool_name'] ?? null,
            'command_name' => $context['command_name'] ?? null,
            'step_name' => $context['step_name'] ?? null,
            'operation' => $context['operation'] ?? null,
            'performance' => [
                'duration_ms' => $context['duration_ms'] ?? $context['execution_time_ms'] ?? null,
                'memory_usage' => $context['memory_usage'] ?? null,
                'cpu_usage' => $context['cpu_usage'] ?? null,
            ],
            'context' => array_diff_key($context, array_flip([
                'channel', 'level', 'event', 'event_name', 'tool_name', 'command_name',
                'step_name', 'operation', 'duration_ms', 'execution_time_ms', 'memory_usage', 'cpu_usage',
            ])),
        ];
    }
}
