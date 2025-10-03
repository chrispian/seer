<?php

namespace App\Services\Commands\DSL\Steps;

abstract class Step
{
    /**
     * Execute the step with given configuration and context
     */
    abstract public function execute(array $config, array $context, bool $dryRun = false): mixed;

    /**
     * Get the step type identifier
     */
    abstract public function getType(): string;

    /**
     * Validate step configuration
     */
    public function validate(array $config): bool
    {
        return true; // Default: no validation
    }
}