<?php

namespace App\Services\Commands\DSL\Steps;

class StepFactory
{
    protected array $stepClasses = [
        // Original step types
        'transform' => TransformStep::class,
        'ai.generate' => AiGenerateStep::class,
        'fragment.create' => FragmentCreateStep::class,
        'search.query' => SearchQueryStep::class,
        'notify' => NotifyStep::class,
        'tool.call' => ToolCallStep::class,

        // Phase 1 DSL extensions for command migration
        'fragment.query' => FragmentQueryStep::class,
        'fragment.update' => FragmentUpdateStep::class,
        'condition' => ConditionStep::class,
        'response.panel' => ResponsePanelStep::class,

        // Additional step types for complex commands
        'database.update' => DatabaseUpdateStep::class,
        'validate' => ValidateStep::class,
        'job.dispatch' => JobDispatchStep::class,

        // Database model operations
        'model.query' => ModelQueryStep::class,
        'model.create' => ModelCreateStep::class,
        'model.update' => ModelUpdateStep::class,
        'model.delete' => ModelDeleteStep::class,

        // Text processing operations
        'text.parse' => TextParseStep::class,

        // Utility steps for data manipulation
        'context.merge' => ContextMergeStep::class,
        'string.format' => StringFormatStep::class,
        'list.map' => ListMapStep::class,
        'data.transform' => DataTransformStep::class,
    ];

    /**
     * Create a step instance by type
     */
    public function create(string $type): Step
    {
        if (! isset($this->stepClasses[$type])) {
            throw new \InvalidArgumentException("Unknown step type: {$type}");
        }

        $stepClass = $this->stepClasses[$type];

        return app($stepClass);
    }

    /**
     * Get all available step types
     */
    public function getAvailableTypes(): array
    {
        return array_keys($this->stepClasses);
    }

    /**
     * Register a custom step type
     */
    public function register(string $type, string $stepClass): void
    {
        $this->stepClasses[$type] = $stepClass;
    }
}
