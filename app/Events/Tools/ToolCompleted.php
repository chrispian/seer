<?php

namespace App\Events\Tools;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToolCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tool,
        public string $status, // 'ok'|'failed'
        public int $durationMs,
        public ?string $invocationId = null,
        public ?string $commandSlug = null,
        public ?int $fragmentId = null,
        public ?int $userId = null
    ) {}
}
