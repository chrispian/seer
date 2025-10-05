<?php

namespace App\Events\Commands;

class CommandCompleted
{
    public function __construct(
        public string $slug, public string $status, public float $durationMs, public ?string $error = null,
        public ?string $workspaceId = null, public ?string $userId = null, public ?string $runId = null
    ) {}
}
