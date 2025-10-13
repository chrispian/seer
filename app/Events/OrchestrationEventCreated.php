<?php

namespace App\Events;

use App\Models\OrchestrationEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrchestrationEventCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OrchestrationEvent $event
    ) {}
}
