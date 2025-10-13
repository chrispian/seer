<?php

use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationAutomationService;
use App\Services\Orchestration\OrchestrationEventService;
use App\Services\Orchestration\OrchestrationReplayService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->eventService = app(OrchestrationEventService::class);
    $this->replayService = app(OrchestrationReplayService::class);
    $this->automationService = app(OrchestrationAutomationService::class);
});

it('emits events with standardized metadata', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'TEST-SPRINT-001',
        'title' => 'Test Sprint',
        'status' => 'planning',
    ]);

    $event = $this->eventService->emitSprintCreated($sprint, 'test-session');

    expect($event)->toBeInstanceOf(OrchestrationEvent::class);
    expect($event->payload)->toHaveKey('actor');
    expect($event->payload)->toHaveKey('timestamp');
    expect($event->payload)->toHaveKey('entity_snapshot');
});

it('can query events by correlation ID', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'TEST-SPRINT-002',
        'title' => 'Test Sprint 2',
        'status' => 'planning',
    ]);

    $event1 = $this->eventService->emitSprintCreated($sprint, 'test-session');
    $correlationId = $event1->correlation_id;

    $sprint->update(['status' => 'active']);
    $this->eventService->emitSprintStatusChanged($sprint, 'planning', 'active', 'test-session');

    $events = OrchestrationEvent::byCorrelation($correlationId)->get();

    expect($events->count())->toBe(1);
});

it('can query events by session key', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'TEST-SPRINT-003',
        'title' => 'Test Sprint 3',
        'status' => 'planning',
    ]);

    $this->eventService->emitSprintCreated($sprint, 'session-123');
    $this->eventService->emitSprintStatusChanged($sprint, 'planning', 'active', 'session-123');

    $events = OrchestrationEvent::bySession('session-123')->get();

    expect($events->count())->toBeGreaterThanOrEqual(2);
});

it('validates event chains correctly', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'TEST-SPRINT-004',
        'title' => 'Test Sprint 4',
        'status' => 'planning',
    ]);

    $event = $this->eventService->emitSprintCreated($sprint);
    
    $validation = $this->replayService->validateEventChain($event->correlation_id);

    expect($validation['valid'])->toBeTrue();
    expect($validation['event_count'])->toBe(1);
});

it('can reconstruct state from events', function () {
    $task = OrchestrationTask::create([
        'task_code' => 'TEST-TASK-001',
        'title' => 'Test Task',
        'status' => 'pending',
        'priority' => 'P2',
    ]);

    $this->eventService->emitTaskCreated($task);
    
    sleep(1);
    
    $task->update(['status' => 'in_progress']);
    $this->eventService->emitTaskStatusChanged($task, 'pending', 'in_progress');

    $state = $this->replayService->reconstructState('task', $task->id, now());

    expect($state)->toHaveKey('state');
    expect($state['event_count'])->toBeGreaterThanOrEqual(2);
});

it('can replay events in dry-run mode', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'TEST-SPRINT-005',
        'title' => 'Test Sprint 5',
        'status' => 'planning',
    ]);

    $event = $this->eventService->emitSprintCreated($sprint);

    $result = $this->replayService->replayEvents($event->correlation_id, dryRun: true);

    expect($result['success'])->toBeTrue();
    expect($result['dry_run'])->toBeTrue();
    expect($result)->toHaveKey('replayed_events');
});

it('archives events correctly', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'TEST-SPRINT-006',
        'title' => 'Test Sprint 6',
        'status' => 'planning',
    ]);

    $event = $this->eventService->emitSprintCreated($sprint);

    $event->update(['archived_at' => now()]);

    $archivedEvents = OrchestrationEvent::archived()->get();
    $activeEvents = OrchestrationEvent::active()->get();

    expect($archivedEvents->contains($event->id))->toBeTrue();
    expect($activeEvents->contains($event->id))->toBeFalse();
});
