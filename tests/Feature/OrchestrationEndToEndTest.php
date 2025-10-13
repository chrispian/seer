<?php

use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationContextBrokerService;
use App\Services\Orchestration\OrchestrationEventService;
use App\Services\Orchestration\OrchestrationFileSyncService;
use App\Services\Orchestration\OrchestrationPMToolsService;
use App\Services\Orchestration\OrchestrationTemplateService;
use Illuminate\Support\Facades\File;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('completes full sprint creation workflow', function () {
    $templateService = app(OrchestrationTemplateService::class);
    $fileSyncService = app(OrchestrationFileSyncService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-test-sprint',
        'title' => 'End-to-End Test Sprint',
        'status' => 'planning',
        'owner' => 'test-team',
        'metadata' => [
            'goal' => 'Validate full workflow',
            'start_date' => now()->toDateString(),
        ],
    ]);

    expect($sprint->exists)->toBeTrue();
    expect($sprint->hash)->not->toBeEmpty();

    $fileSyncService->syncSprintToFile($sprint);

    expect(File::exists($sprint->file_path))->toBeTrue();
});

it('completes full task lifecycle workflow', function () {
    $eventService = app(OrchestrationEventService::class);
    $fileSyncService = app(OrchestrationFileSyncService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-lifecycle-sprint',
        'title' => 'Lifecycle Test Sprint',
        'status' => 'active',
    ]);

    $task = OrchestrationTask::create([
        'task_code' => 'e2e-lifecycle-task',
        'title' => 'End-to-End Lifecycle Task',
        'status' => 'pending',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
        'metadata' => [
            'objectives' => ['Test full lifecycle'],
        ],
    ]);

    expect($task->exists)->toBeTrue();
    
    $eventService->logTaskCreated($task);
    
    $task->update(['status' => 'in_progress']);
    $eventService->logTaskStatusUpdated($task, 'pending', 'in_progress');
    
    $task->update(['status' => 'completed']);
    $eventService->logTaskStatusUpdated($task, 'in_progress', 'completed');

    $events = OrchestrationEvent::where('entity_type', 'task')
        ->where('entity_id', $task->id)
        ->get();

    expect($events->count())->toBeGreaterThanOrEqual(3);
    
    $fileSyncService->syncTaskToFile($task);
});

it('completes agent initialization workflow', function () {
    $contextBroker = app(OrchestrationContextBrokerService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-agent-init-sprint',
        'title' => 'Agent Init Test Sprint',
        'status' => 'active',
    ]);

    $task = OrchestrationTask::create([
        'task_code' => 'e2e-agent-init-task',
        'title' => 'Agent Init Task',
        'status' => 'pending',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
    ]);

    $context = $contextBroker->assembleTaskContext($task->task_code);

    expect($context)->toHaveKey('task');
    expect($context)->toHaveKey('sprint');
    expect($context)->toHaveKey('session');
    expect($context['task']['task_code'])->toBe('e2e-agent-init-task');
    expect($context['sprint']['sprint_code'])->toBe('e2e-agent-init-sprint');
});

it('completes PM tools workflow', function () {
    $pmToolsService = app(OrchestrationPMToolsService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-pm-tools-sprint',
        'title' => 'PM Tools Test Sprint',
        'status' => 'active',
    ]);

    $task1 = OrchestrationTask::create([
        'task_code' => 'pm-task-1',
        'title' => 'PM Task 1',
        'status' => 'completed',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
    ]);

    $task2 = OrchestrationTask::create([
        'task_code' => 'pm-task-2',
        'title' => 'PM Task 2',
        'status' => 'in_progress',
        'priority' => 'P2',
        'sprint_id' => $sprint->id,
    ]);

    $report = $pmToolsService->generateStatusReport('e2e-pm-tools-sprint');

    expect($report)->toHaveKey('sprint_code');
    expect($report)->toHaveKey('summary');
    expect($report['summary']['total_tasks'])->toBe(2);
    expect($report['summary']['completed'])->toBe(1);
    expect($report['summary']['in_progress'])->toBe(1);
    expect($report['summary']['progress_percentage'])->toBe(50.0);
});

it('validates event emission across all operations', function () {
    $eventService = app(OrchestrationEventService::class);
    $pmToolsService = app(OrchestrationPMToolsService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-events-sprint',
        'title' => 'Events Test Sprint',
        'status' => 'planning',
    ]);

    $eventService->emitSprintCreated($sprint);
    
    $sprint->update(['status' => 'active']);
    $eventService->emitSprintStatusChanged($sprint, 'planning', 'active');

    $task = OrchestrationTask::create([
        'task_code' => 'e2e-events-task',
        'title' => 'Events Task',
        'status' => 'pending',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
    ]);

    $eventService->logTaskCreated($task);
    
    $pmToolsService->updateTaskStatus('e2e-events-task', 'in_progress', [
        'emit_event' => true,
        'sync_to_file' => false,
    ]);

    $events = OrchestrationEvent::whereIn('entity_type', ['sprint', 'task'])->get();

    expect($events->count())->toBeGreaterThanOrEqual(4);
    
    $sprintEvents = $events->where('entity_type', 'sprint');
    $taskEvents = $events->where('entity_type', 'task');
    
    expect($sprintEvents->count())->toBeGreaterThanOrEqual(2);
    expect($taskEvents->count())->toBeGreaterThanOrEqual(2);
});

it('validates file sync integration', function () {
    $fileSyncService = app(OrchestrationFileSyncService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-sync-sprint',
        'title' => 'File Sync Test Sprint',
        'status' => 'active',
        'owner' => 'test-team',
        'metadata' => [
            'goal' => 'Test file sync',
        ],
    ]);

    $task = OrchestrationTask::create([
        'task_code' => 'e2e-sync-task',
        'title' => 'File Sync Task',
        'status' => 'in_progress',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
        'metadata' => [
            'objectives' => ['Validate file sync'],
        ],
    ]);

    $fileSyncService->syncSprintToFile($sprint);
    $fileSyncService->syncTaskToFile($task);

    expect(File::exists($sprint->file_path))->toBeTrue();
    expect(File::exists($task->file_path))->toBeTrue();
    
    if (File::exists($sprint->file_path)) {
        $sprintContent = File::get($sprint->file_path);
        expect($sprintContent)->toContain('e2e-sync-sprint');
        expect($sprintContent)->toContain('File Sync Test Sprint');
    }
    
    if (File::exists($task->file_path)) {
        $taskContent = File::get($task->file_path);
        expect($taskContent)->toContain('e2e-sync-task');
        expect($taskContent)->toContain('File Sync Task');
    }
});

it('validates complete agent handoff workflow', function () {
    $contextBroker = app(OrchestrationContextBrokerService::class);
    $eventService = app(OrchestrationEventService::class);
    
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'e2e-handoff-sprint',
        'title' => 'Agent Handoff Sprint',
        'status' => 'active',
    ]);

    $task = OrchestrationTask::create([
        'task_code' => 'e2e-handoff-task',
        'title' => 'Handoff Task',
        'status' => 'in_progress',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
    ]);

    $sessionKey = 'agent-session-' . uniqid();
    
    $eventService->logAgentSessionStarted($task, 1, $sessionKey);
    
    $context = $contextBroker->assembleTaskContext($task->task_code, [
        'session_key' => $sessionKey,
        'agent_id' => 1,
    ]);

    expect($context)->toHaveKey('session');
    expect($context['session']['key'])->toBe($sessionKey);
    
    $eventService->logAgentSessionCompleted($task, 1, $sessionKey, 'Task work completed');

    $sessionEvents = OrchestrationEvent::where('session_key', $sessionKey)->get();
    expect($sessionEvents->count())->toBeGreaterThanOrEqual(2);
});
