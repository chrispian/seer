<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrchestrationEventService
{
    public function emit(
        string $eventType, 
        Model $entity, 
        array $payload = [],
        ?string $sessionKey = null,
        ?int $agentId = null
    ): OrchestrationEvent {
        $entityType = match (get_class($entity)) {
            OrchestrationSprint::class => 'sprint',
            OrchestrationTask::class => 'task',
            default => 'unknown',
        };

        return OrchestrationEvent::create([
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entity->id,
            'correlation_id' => Str::uuid(),
            'session_key' => $sessionKey,
            'agent_id' => $agentId,
            'payload' => array_merge($payload, [
                'entity_snapshot' => $entity->toArray(),
            ]),
            'emitted_at' => now(),
        ]);
    }

    public function emitSprintCreated(OrchestrationSprint $sprint, ?string $sessionKey = null): OrchestrationEvent
    {
        return $this->emit('orchestration.sprint.created', $sprint, [
            'sprint_code' => $sprint->sprint_code,
            'title' => $sprint->title,
        ], $sessionKey);
    }

    public function emitSprintUpdated(OrchestrationSprint $sprint, array $changes, ?string $sessionKey = null): OrchestrationEvent
    {
        return $this->emit('orchestration.sprint.updated', $sprint, [
            'sprint_code' => $sprint->sprint_code,
            'changes' => $changes,
        ], $sessionKey);
    }

    public function emitTaskCreated(OrchestrationTask $task, ?string $sessionKey = null): OrchestrationEvent
    {
        return $this->emit('orchestration.task.created', $task, [
            'task_code' => $task->task_code,
            'title' => $task->title,
            'sprint_id' => $task->sprint_id,
        ], $sessionKey);
    }

    public function emitTaskUpdated(OrchestrationTask $task, array $changes, ?string $sessionKey = null): OrchestrationEvent
    {
        return $this->emit('orchestration.task.updated', $task, [
            'task_code' => $task->task_code,
            'changes' => $changes,
        ], $sessionKey);
    }

    public function emitTaskStatusChanged(
        OrchestrationTask $task, 
        string $oldStatus, 
        string $newStatus, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.task.status_updated', $task, [
            'task_code' => $task->task_code,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ], $sessionKey);
    }
}
