<?php

use App\Services\DelegationMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var DelegationMigrationService $migration */
    $migration = app(DelegationMigrationService::class);
    $migration->import();
});

test('orchestration agents command returns json summary', function () {
    Artisan::call('orchestration:agents', ['--json' => true, '--limit' => 5]);

    $output = Artisan::output();
    $payload = json_decode($output, true);

    expect($payload)->toHaveKey('data');
    expect($payload['data'])->not->toBeEmpty();
    expect($payload['data'][0])->toHaveKeys(['id', 'name', 'slug', 'type', 'status']);
});

test('orchestration sprints command summarises sprint progress', function () {
    Artisan::call('orchestration:sprints', ['--json' => true, '--limit' => 2]);

    $payload = json_decode(Artisan::output(), true);

    expect($payload['data'])->not->toBeEmpty();
    expect($payload['data'][0])->toHaveKeys(['code', 'title', 'stats']);
    expect($payload['data'][0]['stats'])->toHaveKeys(['total', 'completed', 'in_progress']);
});

test('orchestration tasks command lists work items with filters', function () {
    Artisan::call('orchestration:tasks', ['--json' => true, '--limit' => 5, '--delegation-status' => ['completed', 'assigned']]);

    $payload = json_decode(Artisan::output(), true);

    expect($payload['data'])->not->toBeEmpty();
    expect($payload['data'][0])->toHaveKeys(['task_code', 'sprint_code', 'delegation_status']);
});

test('orchestration agent status command updates agent status', function () {
    // First get an agent to test with
    Artisan::call('orchestration:agents', ['--json' => true, '--limit' => 1]);
    $agentsPayload = json_decode(Artisan::output(), true);

    expect($agentsPayload['data'])->not->toBeEmpty();
    $agent = $agentsPayload['data'][0];
    $agentSlug = $agent['slug'];

    // Update the agent status
    Artisan::call('orchestration:agent:status', [
        'agent' => $agentSlug,
        'status' => 'inactive',
        '--json' => true,
    ]);

    $output = Artisan::output();
    $payload = json_decode($output, true);

    expect($payload)->toHaveKey('success');
    expect($payload['success'])->toBe(true);
    expect($payload)->toHaveKey('new_status');
    expect($payload['new_status'])->toBe('inactive');
    expect($payload)->toHaveKey('agent');
    expect($payload['agent']['slug'])->toBe($agentSlug);
});

test('orchestration agent status command handles invalid status', function () {
    // First get an agent to test with
    Artisan::call('orchestration:agents', ['--json' => true, '--limit' => 1]);
    $agentsPayload = json_decode(Artisan::output(), true);

    expect($agentsPayload['data'])->not->toBeEmpty();
    $agent = $agentsPayload['data'][0];
    $agentSlug = $agent['slug'];

    // Try to set an invalid status
    $exitCode = Artisan::call('orchestration:agent:status', [
        'agent' => $agentSlug,
        'status' => 'invalid-status',
    ]);

    expect($exitCode)->toBe(1); // Failure exit code
    expect(Artisan::output())->toContain('Unknown agent status');
});

test('orchestration sprint tasks attach command attaches tasks to sprint', function () {
    // Get a sprint to test with
    Artisan::call('orchestration:sprints', ['--json' => true, '--limit' => 1]);
    $sprintsPayload = json_decode(Artisan::output(), true);

    expect($sprintsPayload['data'])->not->toBeEmpty();
    $sprint = $sprintsPayload['data'][0];
    $sprintCode = $sprint['code'];

    // Get some tasks to attach
    Artisan::call('orchestration:tasks', ['--json' => true, '--limit' => 2]);
    $tasksPayload = json_decode(Artisan::output(), true);

    expect($tasksPayload['data'])->not->toBeEmpty();
    $taskCodes = array_slice(array_column($tasksPayload['data'], 'task_code'), 0, 2);

    // Attach tasks to sprint
    $command = [
        'sprint' => $sprintCode,
        'task' => $taskCodes,
        '--json' => true,
        '--include-tasks' => true,
    ];

    Artisan::call('orchestration:sprint:tasks:attach', $command);

    $output = Artisan::output();
    $payload = json_decode($output, true);

    expect($payload)->toHaveKey('sprint');
    expect($payload['sprint']['code'])->toBe($sprintCode);
    expect($payload['sprint'])->toHaveKey('tasks');
    expect($payload['sprint']['tasks'])->not->toBeEmpty();
    expect($payload['sprint'])->toHaveKey('stats');
});

test('orchestration sprint tasks attach command handles no tasks provided', function () {
    // Get a sprint to test with
    Artisan::call('orchestration:sprints', ['--json' => true, '--limit' => 1]);
    $sprintsPayload = json_decode(Artisan::output(), true);

    expect($sprintsPayload['data'])->not->toBeEmpty();
    $sprint = $sprintsPayload['data'][0];
    $sprintCode = $sprint['code'];

    // Try to attach with no tasks
    $exitCode = Artisan::call('orchestration:sprint:tasks:attach', [
        'sprint' => $sprintCode,
        'task' => [],
    ]);

    expect($exitCode)->toBe(1); // Failure exit code
    expect(Artisan::output())->toContain('At least one task must be provided');
});
