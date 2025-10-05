<?php

namespace App\Decorators;

use App\Services\Commands\DSL\CommandRunner;
use App\Services\Telemetry\CommandTelemetry;
use App\Services\Telemetry\CorrelationContext;

/**
 * Decorator for CommandRunner that adds comprehensive telemetry
 * without modifying the core command execution logic.
 */
class CommandTelemetryDecorator
{
    public function __construct(
        protected CommandRunner $commandRunner
    ) {}

    /**
     * Execute a command with telemetry tracking
     */
    public function execute(string $slug, array $context = [], bool $dryRun = false): array
    {
        $startTime = microtime(true);
        $commandId = 'cmd_'.uniqid();

        // Set up correlation context for this command execution
        if (! CorrelationContext::hasContext()) {
            CorrelationContext::set($commandId);
        }

        CorrelationContext::addContext('command_slug', $slug);
        CorrelationContext::addContext('execution_type', 'dsl');

        CommandTelemetry::logCommandStart($slug, $context, [
            'source_type' => 'dsl',
            'dry_run' => $dryRun,
        ]);

        $success = false;
        $error = null;
        $result = null;
        $metrics = [];

        try {
            // Execute the actual command
            $result = $this->commandRunner->execute($slug, $context, $dryRun);

            $success = $result['success'] ?? false;
            $error = $result['error'] ?? null;

            // Extract metrics from the execution result
            $metrics = $this->extractMetrics($result);

        } catch (\Exception $e) {
            $success = false;
            $error = $e->getMessage();

            CommandTelemetry::logError('command_execution', $error, [
                'command' => $slug,
                'context' => array_keys($context),
                'dry_run' => $dryRun,
            ]);

            // Re-throw the exception to maintain original behavior
            throw $e;
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;

            CommandTelemetry::logCommandComplete(
                $slug,
                $context,
                $duration,
                $success,
                $metrics,
                $error
            );
        }

        return $result;
    }

    /**
     * Extract telemetry metrics from command execution result
     */
    protected function extractMetrics(array $result): array
    {
        $metrics = [
            'total_steps' => count($result['steps'] ?? []),
            'successful_steps' => 0,
            'failed_steps' => 0,
            'step_types' => [],
            'performance' => $result['performance'] ?? [],
        ];

        foreach ($result['steps'] ?? [] as $step) {
            if ($step['success'] ?? false) {
                $metrics['successful_steps']++;
            } else {
                $metrics['failed_steps']++;
            }

            $stepType = $step['type'] ?? 'unknown';
            $metrics['step_types'][$stepType] = ($metrics['step_types'][$stepType] ?? 0) + 1;
        }

        return $metrics;
    }

    /**
     * Static factory method to wrap CommandRunner with telemetry
     */
    public static function wrap(CommandRunner $commandRunner): self
    {
        return new self($commandRunner);
    }
}
