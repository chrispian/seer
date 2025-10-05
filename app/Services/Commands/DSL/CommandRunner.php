<?php

namespace App\Services\Commands\DSL;

use App\Decorators\StepTelemetryDecorator;
use App\Decorators\TemplateEngineTelemetryDecorator;
use App\Services\Commands\CommandPackLoader;
use App\Services\Commands\DSL\Steps\StepFactory;

class CommandRunner
{
    public function __construct(
        protected CommandPackLoader $loader,
        protected TemplateEngine $templateEngine,
        protected StepFactory $stepFactory
    ) {
        // Wrap template engine with telemetry if enabled
        if (config('command-telemetry.enabled', true)) {
            $this->templateEngine = TemplateEngineTelemetryDecorator::wrap($this->templateEngine);
        }
    }

    /**
     * Execute a command by slug with given context
     */
    public function execute(string $slug, array $context = [], bool $dryRun = false): array
    {
        $commandPack = $this->loader->loadCommandPack($slug);

        if (! $commandPack) {
            throw new \InvalidArgumentException("Command pack not found: {$slug}");
        }

        $manifest = $commandPack['manifest'];
        $steps = $manifest['steps'] ?? [];

        $execution = [
            'command' => $slug,
            'context' => $context,
            'steps' => [],
            'success' => true,
            'error' => null,
            'dry_run' => $dryRun,
        ];

        // Build execution context
        $executionContext = $this->buildExecutionContext($context, $commandPack);
        $totalStartTime = microtime(true);

        try {
            foreach ($steps as $stepConfig) {
                $stepResult = $this->executeStep($stepConfig, $executionContext, $dryRun);

                // Add step result to execution
                $execution['steps'][] = $stepResult;

                // Add step output to context for subsequent steps
                if (isset($stepConfig['id']) && isset($stepResult['output'])) {
                    $executionContext['steps'][$stepConfig['id']] = [
                        'output' => $stepResult['output'],
                    ];
                }

                // Stop on error
                if (! $stepResult['success']) {
                    $execution['success'] = false;
                    $execution['error'] = $stepResult['error'];
                    break;
                }
            }

            // Add performance metrics
            $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);
            $execution['performance'] = [
                'total_duration_ms' => $totalDuration,
                'step_count' => count($steps),
                'avg_step_duration_ms' => count($steps) > 0 ? round($totalDuration / count($steps), 2) : 0,
            ];

            // Log performance for analysis
            if ($totalDuration > 1000) { // Log commands taking > 1 second
                \Log::info('Slow Command Execution', [
                    'command' => $slug,
                    'duration_ms' => $totalDuration,
                    'step_count' => count($steps),
                    'dry_run' => $dryRun,
                ]);
            }
        } catch (\Exception $e) {
            $execution['success'] = false;
            $execution['error'] = $e->getMessage();
        }

        return $execution;
    }

    /**
     * Execute a single step
     */
    protected function executeStep(array $stepConfig, array $context, bool $dryRun): array
    {
        $stepType = $stepConfig['type'] ?? 'unknown';
        $stepId = $stepConfig['id'] ?? 'step-'.uniqid();

        $stepResult = [
            'id' => $stepId,
            'type' => $stepType,
            'success' => false,
            'output' => null,
            'error' => null,
            'duration_ms' => 0,
        ];

        $startTime = microtime(true);

        try {
            // Create step handler
            $step = $this->stepFactory->create($stepType);

            // Wrap step with telemetry if enabled
            if (config('command-telemetry.enabled', true)) {
                $step = StepTelemetryDecorator::wrap($step);
            }

            // Render step configuration with context
            $renderedConfig = $this->renderStepConfig($stepConfig, $context);

            // Execute step
            $stepResult['output'] = $step->execute($renderedConfig, $context, $dryRun);
            $stepResult['success'] = true;

        } catch (\Exception $e) {
            $stepResult['error'] = $e->getMessage();
        }

        $stepResult['duration_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $stepResult;
    }

    /**
     * Render step configuration with template engine
     */
    protected function renderStepConfig(array $stepConfig, array $context): array
    {
        $rendered = [];
        $stepType = $stepConfig['type'] ?? '';

        foreach ($stepConfig as $key => $value) {
            // Special handling for condition steps - don't pre-render condition templates
            if ($stepType === 'condition' && $key === 'condition' && is_string($value)) {
                // Pass condition template as-is to let ConditionStep handle evaluation
                $rendered[$key] = $value;
            } elseif (is_string($value)) {
                $rendered[$key] = $this->templateEngine->render($value, $context);
            } elseif (is_array($value)) {
                $rendered[$key] = $this->renderStepConfig($value, $context);
            } else {
                $rendered[$key] = $value;
            }
        }

        return $rendered;
    }

    /**
     * Build execution context from input context
     */
    protected function buildExecutionContext(array $inputContext, array $commandPack): array
    {
        // Merge input context directly instead of nesting under 'ctx'
        // This ensures ctx.body works instead of ctx.ctx.body
        return array_merge($inputContext, [
            'env' => [], // Environment variables (gated)
            'steps' => [], // Step outputs
            'now' => now()->toISOString(),
            'uuid' => \Str::uuid()->toString(),
            'ulid' => \Str::ulid()->toString(),
            'prompts' => $commandPack['prompts'] ?? [],
        ]);
    }
}
