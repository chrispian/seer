<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FragmentAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $fragmentId,
        public array $updates,
        public ?string $userId
    ) {}
}
