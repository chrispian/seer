<?php

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationContextBrokerService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->contextBroker = app(OrchestrationContextBrokerService::class);
});

it('can assemble sprint context', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'test-sprint',
        'title' => 'Test Sprint',
        'status' => 'active',
        'owner' => 'test-user',
        'metadata' => ['goal' => 'Test goal'],
    ]);

    $task1 = OrchestrationTask::create([
        'task_code' => 'task-1',
        'sprint_id' => $sprint->id,
        'title' => 'Task 1',
        'status' => 'pending',
        'priority' => 'P0',
    ]);

    $task2 = OrchestrationTask::create([
        'task_code' => 'task-2',
        'sprint_id' => $sprint->id,
        'title' => 'Task 2',
        'status' => 'completed',
        'priority' => 'P1',
    ]);

    $context = $this->contextBroker->assembleSprintContext('test-sprint');

    expect($context)->toHaveKey('sprint');
    expect($context)->toHaveKey('tasks');
    expect($context)->toHaveKey('progress');
    expect($context['sprint']['code'])->toBe('test-sprint');
    expect($context['tasks'])->toHaveCount(2);
    expect($context['progress']['total_tasks'])->toBe(2);
    expect($context['progress']['completed_tasks'])->toBe(1);
    expect($context['progress']['pending_tasks'])->toBe(1);
});

it('can assemble task context', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'test-sprint',
        'title' => 'Test Sprint',
        'status' => 'active',
    ]);

    $task = OrchestrationTask::create([
        'task_code' => 'test-task',
        'sprint_id' => $sprint->id,
        'title' => 'Test Task',
        'status' => 'in_progress',
        'priority' => 'P0',
        'metadata' => ['objective' => 'Test objective'],
    ]);

    $context = $this->contextBroker->assembleTaskContext('test-task');

    expect($context)->toHaveKey('task');
    expect($context)->toHaveKey('sprint');
    expect($context)->toHaveKey('sprint_progress');
    expect($context['task']['code'])->toBe('test-task');
    expect($context['sprint']['code'])->toBe('test-sprint');
});

it('can initialize agent on task', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'init-sprint',
        'title' => 'Init Sprint',
        'status' => 'active',
    ]);

    $task = OrchestrationTask::create([
        'task_code' => 'init-task',
        'sprint_id' => $sprint->id,
        'title' => 'Init Task',
        'status' => 'pending',
        'priority' => 'P0',
    ]);

    $response = $this->postJson('/api/orchestration/agent/init', [
        'entity_type' => 'task',
        'entity_code' => 'init-task',
        'agent_id' => 123,
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'success',
        'session_key',
        'entity',
        'context',
        'message',
        'next_steps',
    ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('session_key'))->toBeString();
    expect($response->json('context'))->toHaveKey('task');
    expect($response->json('context'))->toHaveKey('sprint');
});

it('can initialize agent on sprint', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'init-sprint-2',
        'title' => 'Init Sprint 2',
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/orchestration/agent/init', [
        'entity_type' => 'sprint',
        'entity_code' => 'init-sprint-2',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
    ]);

    expect($response->json('context'))->toHaveKey('sprint');
    expect($response->json('context'))->toHaveKey('tasks');
    expect($response->json('context'))->toHaveKey('progress');
});

it('can resume session', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'resume-sprint',
        'title' => 'Resume Sprint',
        'status' => 'active',
    ]);

    $initResponse = $this->postJson('/api/orchestration/agent/init', [
        'entity_type' => 'sprint',
        'entity_code' => 'resume-sprint',
    ]);

    $sessionKey = $initResponse->json('session_key');

    $resumeResponse = $this->postJson('/api/orchestration/agent/init', [
        'resume_session' => $sessionKey,
    ]);

    $resumeResponse->assertStatus(200);
    $resumeResponse->assertJson([
        'success' => true,
        'resumed' => true,
        'session_key' => $sessionKey,
    ]);

    expect($resumeResponse->json('session_state'))->toHaveKey('started_at');
    expect($resumeResponse->json('session_state'))->toHaveKey('last_activity_at');
});

it('returns 404 for non-existent task', function () {
    $response = $this->postJson('/api/orchestration/agent/init', [
        'entity_type' => 'task',
        'entity_code' => 'non-existent-task',
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
    ]);
});

it('can get session context', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'context-sprint',
        'title' => 'Context Sprint',
        'status' => 'active',
    ]);

    $initResponse = $this->postJson('/api/orchestration/agent/init', [
        'entity_type' => 'sprint',
        'entity_code' => 'context-sprint',
    ]);

    $sessionKey = $initResponse->json('session_key');

    $contextResponse = $this->getJson("/api/orchestration/sessions/{$sessionKey}/context");

    $contextResponse->assertStatus(200);
    $contextResponse->assertJsonStructure([
        'success',
        'context' => [
            'session',
            'entity_context',
            'session_events',
        ],
    ]);
});
