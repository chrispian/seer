<?php

namespace App\Events\Tools;

class ToolCompleted
{
    public function __construct(
        public string $tool, public array $response, public float $durationMs, public string $status = 'ok',
        public ?string $commandSlug = null, public ?string $fragmentId = null, public ?string $workspaceId = null, public ?string $userId = null, public ?string $invocationId = null
    ) {}
}
