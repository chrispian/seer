<?php

namespace App\Services\Orchestration;

use App\Events\OrchestrationEventCreated;
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
        ?int $agentId = null,
        ?string $correlationId = null,
        ?array $correlationChain = null
    ): OrchestrationEvent {
        $entityType = match (get_class($entity)) {
            OrchestrationSprint::class => 'sprint',
            OrchestrationTask::class => 'task',
            default => 'unknown',
        };

        // Standardize payload metadata
        $standardizedPayload = array_merge($payload, [
            'actor' => $agentId ?? auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'entity_snapshot' => $entity->toArray(),
        ]);

        if ($correlationChain) {
            $standardizedPayload['correlation_chain'] = $correlationChain;
        }

        $event = OrchestrationEvent::create([
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entity->id,
            'correlation_id' => $correlationId ?? Str::uuid(),
            'session_key' => $sessionKey,
            'agent_id' => $agentId,
            'payload' => $standardizedPayload,
            'emitted_at' => now(),
        ]);

        event(new OrchestrationEventCreated($event));

        return $event;
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
            'changes' => ['status' => ['from' => $oldStatus, 'to' => $newStatus]],
        ], $sessionKey);
    }

    public function emitSprintMetadataUpdated(
        OrchestrationSprint $sprint, 
        array $oldMetadata, 
        array $newMetadata, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.sprint.metadata_updated', $sprint, [
            'sprint_code' => $sprint->sprint_code,
            'changes' => [
                'metadata' => [
                    'from' => $oldMetadata,
                    'to' => $newMetadata,
                ]
            ],
        ], $sessionKey);
    }

    public function emitSprintStatusChanged(
        OrchestrationSprint $sprint, 
        string $oldStatus, 
        string $newStatus, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.sprint.status_changed', $sprint, [
            'sprint_code' => $sprint->sprint_code,
            'changes' => ['status' => ['from' => $oldStatus, 'to' => $newStatus]],
        ], $sessionKey);
    }

    public function emitTaskAssigned(
        OrchestrationTask $task, 
        ?int $agentId, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.task.assigned', $task, [
            'task_code' => $task->task_code,
            'assigned_to' => $agentId,
        ], $sessionKey, $agentId);
    }

    public function emitTaskBlocked(
        OrchestrationTask $task, 
        string $reason, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.task.blocked', $task, [
            'task_code' => $task->task_code,
            'reason' => $reason,
            'blocked_at' => now()->toIso8601String(),
        ], $sessionKey);
    }

    public function emitTaskPriorityChanged(
        OrchestrationTask $task, 
        string $oldPriority, 
        string $newPriority, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.task.priority_changed', $task, [
            'task_code' => $task->task_code,
            'changes' => ['priority' => ['from' => $oldPriority, 'to' => $newPriority]],
        ], $sessionKey);
    }

    public function emitTaskProgressUpdated(
        OrchestrationTask $task, 
        int $oldProgress, 
        int $newProgress, 
        ?string $sessionKey = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.task.progress_updated', $task, [
            'task_code' => $task->task_code,
            'changes' => ['progress' => ['from' => $oldProgress, 'to' => $newProgress]],
        ], $sessionKey);
    }

    public function emitSessionStarted(
        Model $entity, 
        string $sessionKey, 
        ?int $agentId = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.session.started', $entity, [
            'session_key' => $sessionKey,
            'started_at' => now()->toIso8601String(),
        ], $sessionKey, $agentId);
    }

    public function emitSessionResumed(
        Model $entity, 
        string $sessionKey, 
        ?int $agentId = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.session.resumed', $entity, [
            'session_key' => $sessionKey,
            'resumed_at' => now()->toIso8601String(),
        ], $sessionKey, $agentId);
    }

    public function emitContextAssembled(
        Model $entity, 
        array $contextData, 
        ?string $sessionKey = null, 
        ?int $agentId = null
    ): OrchestrationEvent {
        return $this->emit('orchestration.context.assembled', $entity, [
            'context_keys' => array_keys($contextData),
            'context_size' => strlen(json_encode($contextData)),
            'assembled_at' => now()->toIso8601String(),
        ], $sessionKey, $agentId);
    }
}
