<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrchestrationReplayService
{
    public function replayEvents(string $correlationId, bool $dryRun = true): array
    {
        $events = OrchestrationEvent::byCorrelation($correlationId)->get();

        if ($events->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No events found for correlation ID',
                'correlation_id' => $correlationId,
            ];
        }

        $validated = $this->validateEventChain($correlationId);
        if (!$validated['valid']) {
            return [
                'success' => false,
                'message' => 'Event chain validation failed',
                'validation' => $validated,
            ];
        }

        $replayed = [];
        foreach ($events as $event) {
            if ($dryRun) {
                $replayed[] = [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'would_apply' => $this->describeEventApplication($event),
                ];
            } else {
                $result = $this->applyEvent($event);
                $replayed[] = [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'applied' => $result,
                ];
            }
        }

        return [
            'success' => true,
            'correlation_id' => $correlationId,
            'event_count' => $events->count(),
            'dry_run' => $dryRun,
            'replayed_events' => $replayed,
        ];
    }

    public function reconstructState(string $entityType, int $entityId, $timestamp): ?array
    {
        $timestampCarbon = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);

        $events = OrchestrationEvent::byEntity($entityType, $entityId)
            ->where('emitted_at', '<=', $timestampCarbon)
            ->orderBy('emitted_at', 'asc')
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        $state = [];
        foreach ($events as $event) {
            $state = $this->applyEventToState($state, $event);
        }

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reconstructed_at' => $timestampCarbon->toIso8601String(),
            'event_count' => $events->count(),
            'state' => $state,
        ];
    }

    public function validateEventChain(string $correlationId): array
    {
        $events = OrchestrationEvent::byCorrelation($correlationId)->get();

        if ($events->isEmpty()) {
            return [
                'valid' => false,
                'errors' => ['No events found'],
            ];
        }

        $errors = [];
        $previousTimestamp = null;

        foreach ($events as $event) {
            if ($previousTimestamp && $event->emitted_at < $previousTimestamp) {
                $errors[] = "Event {$event->id} has timestamp before previous event";
            }
            
            if (!$event->payload || !is_array($event->payload)) {
                $errors[] = "Event {$event->id} has invalid payload";
            }

            $previousTimestamp = $event->emitted_at;
        }

        return [
            'valid' => empty($errors),
            'event_count' => $events->count(),
            'errors' => $errors,
        ];
    }

    private function describeEventApplication(OrchestrationEvent $event): string
    {
        return match($event->event_type) {
            'orchestration.sprint.created' => 'Would create sprint: ' . ($event->payload['sprint_code'] ?? 'unknown'),
            'orchestration.sprint.updated' => 'Would update sprint with changes: ' . json_encode($event->payload['changes'] ?? []),
            'orchestration.sprint.status_changed' => 'Would change sprint status: ' . 
                ($event->payload['changes']['status']['from'] ?? '?') . ' → ' . 
                ($event->payload['changes']['status']['to'] ?? '?'),
            'orchestration.task.created' => 'Would create task: ' . ($event->payload['task_code'] ?? 'unknown'),
            'orchestration.task.updated' => 'Would update task with changes: ' . json_encode($event->payload['changes'] ?? []),
            'orchestration.task.status_updated' => 'Would change task status: ' . 
                ($event->payload['old_status'] ?? '?') . ' → ' . 
                ($event->payload['new_status'] ?? '?'),
            default => 'Would process event: ' . $event->event_type,
        };
    }

    private function applyEvent(OrchestrationEvent $event): bool
    {
        try {
            Log::info('Replaying event', [
                'event_id' => $event->id,
                'event_type' => $event->event_type,
                'entity_type' => $event->entity_type,
                'entity_id' => $event->entity_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to replay event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function applyEventToState(array $state, OrchestrationEvent $event): array
    {
        if (!$event->payload || !isset($event->payload['entity_snapshot'])) {
            return $state;
        }

        return array_merge($state, $event->payload['entity_snapshot']);
    }
}
