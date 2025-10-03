<?php

namespace App\Events\Commands;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $slug,
        public string $status, // 'ok'|'failed'
        public int $durationMs,
        public ?int $userId = null,
        public ?string $runId = null
    ) {}
}