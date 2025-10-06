<?php

use App\Models\AgentProfile;
use App\Models\Sprint;
use App\Models\WorkItem;
use App\Services\DelegationMigrationService;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentsListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintsListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TasksListTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('tool-crate.orchestration.agent_model', AgentProfile::class);
    config()->set('tool-crate.orchestration.sprint_model', Sprint::class);
    config()->set('tool-crate.orchestration.work_item_model', WorkItem::class);

    /** @var DelegationMigrationService $migration */
    $migration = app(DelegationMigrationService::class);
    $migration->import(['sprint' => ['SPRINT-62']]);
});

test('agents list tool returns filtered agent summaries', function () {
    $tool = new AgentsListTool();

    $response = $tool->handle(new Request([
        'status' => ['active'],
        'limit' => 3,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['data'])->not->toBeEmpty();
    $agent = $payload['data'][0];
    expect($agent)->toHaveKeys(['id', 'name', 'slug', 'status', 'type']);
    expect($payload['meta']['count'])->toBeGreaterThan(0);
});

test('sprints list tool includes progress stats and optional tasks', function () {
    $tool = new SprintsListTool();

    $response = $tool->handle(new Request([
        'code' => ['SPRINT-62'],
        'details' => true,
        'tasks_limit' => 2,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['data'])->toHaveCount(1);
    $sprint = $payload['data'][0];
    expect($sprint['code'])->toBe('SPRINT-62');
    expect($sprint['stats'])->toHaveKeys(['total', 'completed', 'in_progress', 'blocked', 'unassigned']);
    expect($sprint['tasks'])->toBeArray();
    expect(count($sprint['tasks']))->toBeLessThanOrEqual(2);
});

test('tasks list tool filters by sprint and delegation status', function () {
    $tool = new TasksListTool();

    $response = $tool->handle(new Request([
        'sprint' => ['SPRINT-62'],
        'delegation_status' => ['completed', 'assigned'],
        'limit' => 5,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['data'])->not->toBeEmpty();
    $task = $payload['data'][0];
    expect($task)->toHaveKeys(['task_code', 'sprint_code', 'delegation_status']);
    expect($task['sprint_code'])->toBe('SPRINT-62');
    expect(in_array($task['delegation_status'], ['completed', 'assigned', 'in_progress']))->toBeTrue();
});
