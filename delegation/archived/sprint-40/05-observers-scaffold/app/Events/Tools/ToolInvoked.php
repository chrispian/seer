<?php

namespace App\Events\Tools;

class ToolInvoked
{
    public function __construct(
        public string $tool, public array $args, public ?string $commandSlug = null, public ?string $fragmentId = null, public ?string $workspaceId = null, public ?string $userId = null, public ?string $invocationId = null
    ) {}
}
