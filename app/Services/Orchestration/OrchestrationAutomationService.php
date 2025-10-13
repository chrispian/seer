<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Illuminate\Support\Facades\Log;

class OrchestrationAutomationService
{
    private array $rules = [];

    public function __construct(
        private OrchestrationEventService $eventService
    ) {
        $this->registerDefaultRules();
    }

    public function registerRule(string $trigger, callable $action, ?string $name = null): void
    {
        $this->rules[] = [
            'name' => $name ?? 'rule_' . count($this->rules),
            'trigger' => $trigger,
            'action' => $action,
        ];
    }

    public function evaluateRules(OrchestrationEvent $event): void
    {
        foreach ($this->rules as $rule) {
            if ($this->matchesTrigger($event, $rule['trigger'])) {
                try {
                    $action = $rule['action'];
                    $action($event, $this);
                    
                    Log::info('Orchestration automation rule executed', [
                        'rule' => $rule['name'],
                        'event_type' => $event->event_type,
                        'entity_type' => $event->entity_type,
                        'entity_id' => $event->entity_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Orchestration automation rule failed', [
                        'rule' => $rule['name'],
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function executeAction(string $actionType, array $context): mixed
    {
        return match($actionType) {
            'update_sprint_status' => $this->updateSprintStatus($context),
            'create_notification' => $this->createNotification($context),
            'emit_alert' => $this->emitAlert($context),
            'initialize_session' => $this->initializeSession($context),
            default => null,
        };
    }

    private function registerDefaultRules(): void
    {
        // Rule 1: When all tasks in sprint completed → update sprint status to completed
        $this->registerRule(
            'orchestration.task.status_updated',
            function (OrchestrationEvent $event, self $service) {
                if ($event->payload['new_status'] ?? null === 'completed') {
                    $task = OrchestrationTask::find($event->entity_id);
                    if (!$task || !$task->sprint_id) {
                        return;
                    }

                    $sprint = OrchestrationSprint::find($task->sprint_id);
                    if (!$sprint) {
                        return;
                    }

                    $allTasksCompleted = $sprint->tasks()
                        ->where('status', '!=', 'completed')
                        ->count() === 0;

                    if ($allTasksCompleted && $sprint->status !== 'completed') {
                        $oldStatus = $sprint->status;
                        $sprint->update(['status' => 'completed']);
                        
                        $this->eventService->emitSprintStatusChanged(
                            $sprint,
                            $oldStatus,
                            'completed',
                            $event->session_key
                        );
                    }
                }
            },
            'auto_complete_sprint'
        );

        // Rule 2: When task blocked → create notification event
        $this->registerRule(
            'orchestration.task.blocked',
            function (OrchestrationEvent $event, self $service) {
                $task = OrchestrationTask::find($event->entity_id);
                if (!$task) {
                    return;
                }

                $service->executeAction('create_notification', [
                    'type' => 'task_blocked',
                    'task_code' => $task->task_code,
                    'reason' => $event->payload['reason'] ?? 'Unknown',
                ]);
            },
            'notify_on_task_blocked'
        );

        // Rule 3: When task priority → P0, emit alert event
        $this->registerRule(
            'orchestration.task.priority_changed',
            function (OrchestrationEvent $event, self $service) {
                if (($event->payload['changes']['priority']['to'] ?? null) === 'P0') {
                    $task = OrchestrationTask::find($event->entity_id);
                    if (!$task) {
                        return;
                    }

                    $service->executeAction('emit_alert', [
                        'alert_type' => 'high_priority_task',
                        'task_code' => $task->task_code,
                        'priority' => 'P0',
                    ]);
                }
            },
            'alert_on_p0_priority'
        );

        // Rule 4: When sprint status → active, emit session initialization event
        $this->registerRule(
            'orchestration.sprint.status_changed',
            function (OrchestrationEvent $event, self $service) {
                if (($event->payload['changes']['status']['to'] ?? null) === 'active') {
                    $sprint = OrchestrationSprint::find($event->entity_id);
                    if (!$sprint) {
                        return;
                    }

                    $service->executeAction('initialize_session', [
                        'sprint_id' => $sprint->id,
                        'sprint_code' => $sprint->sprint_code,
                    ]);
                }
            },
            'initialize_on_sprint_active'
        );
    }

    private function matchesTrigger(OrchestrationEvent $event, string $trigger): bool
    {
        return $event->event_type === $trigger;
    }

    private function updateSprintStatus(array $context): void
    {
        Log::info('Automation: Update sprint status', $context);
    }

    private function createNotification(array $context): void
    {
        Log::info('Automation: Create notification', $context);
    }

    private function emitAlert(array $context): void
    {
        Log::info('Automation: Emit alert', $context);
    }

    private function initializeSession(array $context): void
    {
        Log::info('Automation: Initialize session', $context);
    }
}
