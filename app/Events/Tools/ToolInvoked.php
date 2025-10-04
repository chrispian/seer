<?php

namespace App\Events\Tools;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToolInvoked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tool,
        public ?string $invocationId = null,
        public ?string $commandSlug = null,
        public ?int $fragmentId = null,
        public ?int $userId = null
    ) {}
}
