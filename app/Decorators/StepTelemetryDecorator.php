<?php

namespace App\Decorators;

use App\Services\Commands\DSL\Steps\Step;
use App\Services\Telemetry\CommandTelemetry;

/**
 * Decorator for DSL steps that adds telemetry tracking
 * without modifying the core step execution logic.
 */
class StepTelemetryDecorator extends Step
{
    public function __construct(
        protected Step $step
    ) {}

    public function getType(): string
    {
        return $this->step->getType();
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $stepType = $this->step->getType();
        $stepId = $config['id'] ?? 'step_'.uniqid();
        $startTime = microtime(true);

        CommandTelemetry::logStepStart($stepType, $stepId, $config);

        $success = false;
        $error = null;
        $result = null;
        $metrics = [];

        try {
            // Execute the actual step
            $result = $this->step->execute($config, $context, $dryRun);
            $success = true;

            // Extract step-specific metrics
            $metrics = $this->extractStepMetrics($stepType, $result, $config, $context);

        } catch (\Exception $e) {
            $success = false;
            $error = $e->getMessage();

            CommandTelemetry::logError('step_execution', $error, [
                'step_type' => $stepType,
                'step_id' => $stepId,
                'config_keys' => array_keys($config),
            ]);

            // Re-throw the exception to maintain original behavior
            throw $e;
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;

            CommandTelemetry::logStepComplete(
                $stepType,
                $stepId,
                $duration,
                $success,
                $metrics,
                $error
            );
        }

        return $result;
    }

    public function validate(array $config): bool
    {
        return $this->step->validate($config);
    }

    /**
     * Extract step-specific telemetry metrics
     */
    protected function extractStepMetrics(string $stepType, mixed $result, array $config, array $context): array
    {
        $metrics = [];

        switch ($stepType) {
            case 'ai.generate':
                $metrics['prompt_length'] = strlen($config['prompt'] ?? '');
                $metrics['max_tokens'] = $config['max_tokens'] ?? null;
                $metrics['cache_enabled'] = $config['cache'] ?? false;
                $metrics['expect_type'] = $config['expect'] ?? 'text';

                if (is_string($result)) {
                    $metrics['response_length'] = strlen($result);
                } elseif (is_array($result)) {
                    $metrics['response_keys'] = count($result);
                }
                break;

            case 'condition':
                if (is_array($result)) {
                    $metrics['condition_result'] = $result['condition_result'] ?? null;
                    $metrics['executed_branch'] = $result['executed_branch'] ?? null;
                    $metrics['steps_executed_count'] = count($result['steps_executed'] ?? []);
                }
                break;

            case 'fragment.query':
            case 'fragment.create':
            case 'fragment.update':
                if (is_array($result)) {
                    $metrics['fragment_count'] = count($result);
                }
                break;

            case 'database.update':
            case 'model.query':
            case 'model.create':
            case 'model.update':
            case 'model.delete':
                if (is_array($result) && isset($result['affected_rows'])) {
                    $metrics['affected_rows'] = $result['affected_rows'];
                }
                break;

            case 'search.query':
                if (is_array($result)) {
                    $metrics['result_count'] = count($result);
                    $metrics['query_length'] = strlen($config['query'] ?? '');
                }
                break;

            case 'tool.call':
                $metrics['tool_name'] = $config['tool'] ?? 'unknown';
                if (isset($config['arguments'])) {
                    $metrics['argument_count'] = count($config['arguments']);
                }
                break;

            case 'notify':
                $metrics['notification_type'] = $config['type'] ?? 'info';
                $metrics['has_panel_data'] = isset($config['panel_data']);
                break;

            case 'transform':
            case 'data.transform':
                $metrics['transform_type'] = $config['type'] ?? 'unknown';
                break;

            case 'validate':
                if (is_array($result)) {
                    $metrics['validation_passed'] = $result['valid'] ?? false;
                    $metrics['error_count'] = count($result['errors'] ?? []);
                }
                break;
        }

        return $metrics;
    }

    /**
     * Static factory method to wrap a step with telemetry
     */
    public static function wrap(Step $step): self
    {
        return new self($step);
    }
}
