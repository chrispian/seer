<?php

namespace App\Services\Commands\DSL\Steps;

class StepFactory
{
    protected array $stepClasses = [
        'transform' => TransformStep::class,
        'ai.generate' => AiGenerateStep::class,
        'fragment.create' => FragmentCreateStep::class,
        'search.query' => SearchQueryStep::class,
        'notify' => NotifyStep::class,
        'tool.call' => ToolCallStep::class,
    ];

    /**
     * Create a step instance by type
     */
    public function create(string $type): Step
    {
        if (!isset($this->stepClasses[$type])) {
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