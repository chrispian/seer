<?php

namespace App\Listeners;

use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrchestrationEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private OrchestrationAutomationService $automationService
    ) {}

    public function handle(OrchestrationEvent $event): void
    {
        $this->automationService->evaluateRules($event);
    }

    public function shouldQueue(OrchestrationEvent $event): bool
    {
        return true;
    }
}
