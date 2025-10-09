<?php

use App\Enums\AgentMode;
use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\AgentProfile;
use App\Models\Sprint;
use App\Models\TaskAssignment;
use App\Models\WorkItem;
use App\Services\AgentOrchestrationService;
use App\Services\SprintOrchestrationService;
use App\Services\TaskOrchestrationService;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentDetailTool;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentSaveTool;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentsListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentStatusTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintDetailTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintSaveTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintsListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintStatusTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintTasksAttachTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TaskAssignTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TaskDetailTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TasksListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TaskStatusTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;

uses(RefreshDatabase::class);

function sampleTaskCode(): string
{
    return 'TEST-TASK-001';
}

function sampleAgentSlug(): string
{
    return 'test-agent';
}

function createSampleAgent(string $name = 'Temp Agent'): AgentProfile
{
    return AgentProfile::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(6),
        'type' => AgentType::BackendEngineer->value,
        'mode' => AgentMode::Implementation->value,
        'status' => AgentStatus::Active->value,
    ]);
}

beforeEach(function () {
    config()->set('tool-crate.orchestration.agent_model', AgentProfile::class);
    config()->set('tool-crate.orchestration.sprint_model', Sprint::class);
    config()->set('tool-crate.orchestration.work_item_model', WorkItem::class);
    config()->set('tool-crate.orchestration.task_service', TaskOrchestrationService::class);
    config()->set('tool-crate.orchestration.agent_service', AgentOrchestrationService::class);
    config()->set('tool-crate.orchestration.sprint_service', SprintOrchestrationService::class);

    // Create test data directly instead of relying on delegation import
    $sprint = Sprint::create([
        'code' => 'SPRINT-TEST',
        'title' => 'Test Sprint',
        'description' => 'Test sprint for orchestration tools',
        'status' => 'active',
        'metadata' => ['test' => true],
    ]);

    $workItem = WorkItem::create([
        'code' => 'TEST-TASK-001',
        'title' => 'Test Task',
        'description' => 'Test task for orchestration tools',
        'status' => 'todo',
        'priority' => 'medium',
        'metadata' => [
            'task_code' => 'TEST-TASK-001',
            'sprint_code' => 'SPRINT-TEST',
        ],
        'estimated_hours' => 4.0,
    ]);

    AgentProfile::create([
        'name' => 'Test Agent',
        'slug' => 'test-agent',
        'type' => AgentType::BackendEngineer->value,
        'mode' => AgentMode::Implementation->value,
        'status' => AgentStatus::Active->value,
    ]);
});

test('agents list tool returns filtered agent summaries', function () {
    $tool = new AgentsListTool;

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

test('agent detail tool returns profile snapshot', function () {
    $tool = new AgentDetailTool;

    $response = $tool->handle(new Request([
        'agent' => sampleAgentSlug(),
        'assignments_limit' => 3,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['agent'])->toHaveKeys(['id', 'name', 'slug', 'status', 'capabilities']);
    expect($payload['stats'])->toHaveKeys(['assignments_total', 'assignments_active', 'assignments_completed']);
});

test('agent save tool upserts profile data', function () {
    $tool = new AgentSaveTool;

    $response = $tool->handle(new Request([
        'name' => 'Orchestration Ops Agent',
        'type' => AgentType::FrontendEngineer->value,
        'mode' => AgentMode::Implementation->value,
        'status' => AgentStatus::Active->value,
        'capabilities' => ['react', 'tailwind'],
        'constraints' => ['no_production_access'],
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['agent']['name'])->toBe('Orchestration Ops Agent');
    expect($payload['agent']['capabilities'])->toContain('react');

    expect(AgentProfile::where('slug', $payload['agent']['slug'])->exists())->toBeTrue();
});

test('agent status tool toggles state', function () {
    $agent = createSampleAgent('Lifecycle Agent');

    $tool = new AgentStatusTool;

    $response = $tool->handle(new Request([
        'agent' => $agent->slug,
        'status' => 'archived',
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['agent']['status'])->toBe(AgentStatus::Archived->value);

    $agent->refresh();
    expect($agent->status)->toBe(AgentStatus::Archived);
});

test('sprints list tool includes progress stats and optional tasks', function () {
    $tool = new SprintsListTool;

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
    $tool = new TasksListTool;

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

test('task assign tool creates assignment and updates delegation status', function () {
    $taskCode = sampleTaskCode();
    $agentSlug = sampleAgentSlug();

    $tool = new TaskAssignTool;

    $response = $tool->handle(new Request([
        'task' => $taskCode,
        'agent' => $agentSlug,
        'status' => 'assigned',
        'note' => 'Assigned via ToolCrate test',
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['assignment'])->toHaveKeys(['id', 'agent_slug', 'status']);
    expect($payload['assignment']['agent_slug'])->toBe($agentSlug);
    expect($payload['task']['delegation_status'])->toBe('assigned');

    $workItem = WorkItem::where('metadata->task_code', $taskCode)->first();
    expect($workItem->delegation_status)->toBe('assigned');
    expect($workItem->assignee_type)->toBe('agent');
    expect(TaskAssignment::where('work_item_id', $workItem->id)->count())->toBe(1);
});

test('task status tool transitions delegation state and assignment status', function () {
    $taskCode = sampleTaskCode();
    $agentSlug = sampleAgentSlug();

    (new TaskAssignTool)->handle(new Request([
        'task' => $taskCode,
        'agent' => $agentSlug,
    ]));

    $tool = new TaskStatusTool;
    $response = $tool->handle(new Request([
        'task' => $taskCode,
        'status' => 'in_progress',
        'note' => 'Work started',
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['task']['delegation_status'])->toBe('in_progress');
    expect($payload['current_assignment']['status'])->toBe('started');
    expect($payload['task']['delegation_history'])->not->toBeEmpty();
});

test('task detail tool returns delegation history and assignments', function () {
    $taskCode = sampleTaskCode();
    $agentSlug = sampleAgentSlug();

    (new TaskAssignTool)->handle(new Request([
        'task' => $taskCode,
        'agent' => $agentSlug,
        'status' => 'assigned',
    ]));

    (new TaskStatusTool)->handle(new Request([
        'task' => $taskCode,
        'status' => 'in_progress',
        'note' => 'Kick-off',
    ]));

    $tool = new TaskDetailTool;
    $response = $tool->handle(new Request([
        'task' => $taskCode,
        'assignments_limit' => 5,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['task'])->toHaveKeys(['task_code', 'delegation_status', 'delegation_history']);
    expect($payload['task']['delegation_history'])->not->toBeEmpty();
    expect($payload['assignments'])->not->toBeEmpty();
    expect($payload['current_assignment']['agent_slug'])->toBe($agentSlug);
});

test('sprint detail tool returns sprint snapshot', function () {
    $tool = new SprintDetailTool;

    $response = $tool->handle(new Request([
        'sprint' => 'SPRINT-62',
        'tasks_limit' => 3,
        'include_assignments' => true,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['sprint'])->toHaveKeys(['code', 'stats', 'tasks']);
    expect($payload['sprint']['code'])->toBe('SPRINT-62');
    expect($payload['sprint']['stats']['total'])->toBeGreaterThan(0);
});

test('sprint save tool upserts metadata', function () {
    $tool = new SprintSaveTool;

    $response = $tool->handle(new Request([
        'code' => 'SPRINT-90',
        'title' => 'Sprint Ninety',
        'priority' => 'High',
        'status' => 'Planned',
        'notes' => ['Kickoff pending'],
        'starts_on' => '2025-02-01',
        'ends_on' => '2025-02-05',
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['sprint']['code'])->toBe('SPRINT-90');
    expect($payload['sprint']['title'])->toBe('Sprint Ninety');
    expect($payload['sprint']['priority'])->toBe('High');
    expect($payload['sprint']['notes'])->toContain('Kickoff pending');
});

test('sprint status tool updates status meta and appends note', function () {
    (new SprintSaveTool)->handle(new Request([
        'code' => 'SPRINT-91',
        'title' => 'Sprint Ninety One',
    ]));

    $tool = new SprintStatusTool;
    $response = $tool->handle(new Request([
        'sprint' => 'SPRINT-91',
        'status' => 'In Progress',
        'note' => 'Stand-up complete',
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['sprint']['status'])->toBe('In Progress');
    expect($payload['sprint']['notes'])->toContain('Stand-up complete');
});

test('sprint attach tasks tool associates work items', function () {
    (new SprintSaveTool)->handle(new Request([
        'code' => 'SPRINT-92',
        'title' => 'Sprint Ninety Two',
    ]));

    $attach = new SprintTasksAttachTool;
    $taskCode = sampleTaskCode();

    $response = $attach->handle(new Request([
        'sprint' => 'SPRINT-92',
        'tasks' => [$taskCode],
        'include_assignments' => false,
    ]));

    $payload = json_decode((string) $response->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['sprint']['code'])->toBe('SPRINT-92');
    expect($payload['sprint']['tasks'])->not->toBeEmpty();
    expect(array_column($payload['sprint']['tasks'], 'task_code'))->toContain($taskCode);

    $workItem = WorkItem::where('metadata->task_code', $taskCode)->first();
    expect(Arr::get($workItem->metadata, 'sprint_code'))->toBe('SPRINT-92');
});
