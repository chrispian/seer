<?php

namespace App\Contracts;

interface ToolContract
{
    public function name(): string;

    public function scope(): string;

    public function inputSchema(): array;

    public function outputSchema(): array;

    /**
     * Execute the tool with validated payload.
     */
    public function run(array $payload): array;
}
