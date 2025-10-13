<?php

namespace App\Listeners;

use App\Events\OrchestrationEventCreated;
use App\Services\Orchestration\OrchestrationAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrchestrationEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private OrchestrationAutomationService $automationService
    ) {}

    public function handle(OrchestrationEventCreated $event): void
    {
        $this->automationService->evaluateRules($event->event);
    }

    public function shouldQueue(OrchestrationEventCreated $event): bool
    {
        return true;
    }
}

    public function shouldQueue(OrchestrationEvent $event): bool
    {
        return true;
    }
}
