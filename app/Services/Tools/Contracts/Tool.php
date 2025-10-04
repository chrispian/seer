<?php

namespace App\Services\Tools\Contracts;

interface Tool
{
    /**
     * Get the unique slug identifier for this tool
     */
    public function slug(): string;

    /**
     * Get the capabilities this tool provides
     */
    public function capabilities(): array;

    /**
     * Execute the tool with given arguments and context
     */
    public function call(array $args, array $context = []): array;

    /**
     * Get the tool's configuration schema for validation
     */
    public function getConfigSchema(): array;

    /**
     * Check if the tool is enabled and properly configured
     */
    public function isEnabled(): bool;
}
