<?php

use App\Enums\AgentStatus;
use App\Models\AgentProfile;
use App\Models\TaskAssignment;
use App\Models\WorkItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agent profile creation and relationships', function () {
    $agent = AgentProfile::create([
        'name' => 'Test Backend Engineer',
        'slug' => 'test-backend-engineer',
        'type' => 'backend-engineer',
        'mode' => 'implementation',
        'description' => 'A test backend engineering agent',
        'capabilities' => ['php', 'laravel', 'mysql'],
        'constraints' => ['no_production_access'],
        'tools' => ['composer', 'artisan'],
        'metadata' => ['version' => '1.0'],
        'status' => 'active'
    ]);

    expect($agent->id)->not->toBeNull();
    expect($agent->slug)->toBe('test-backend-engineer');
    expect($agent->capabilities)->toBe(['php', 'laravel', 'mysql']);
    expect($agent->status)->toBe(AgentStatus::Active);
});

test('work item orchestration fields', function () {
    $agent = AgentProfile::create([
        'name' => 'Test Agent',
        'slug' => 'test-agent',
        'type' => 'backend-engineer',
        'mode' => 'implementation',
        'status' => 'active'
    ]);

    $workItem = WorkItem::create([
        'type' => 'task',
        'status' => 'todo',
        'assignee_type' => 'agent',
        'assignee_id' => $agent->id,
        'delegation_status' => 'assigned',
        'delegation_context' => ['priority' => 'high'],
        'delegation_history' => [['action' => 'assigned', 'timestamp' => now()->toISOString()]],
        'estimated_hours' => 2.5,
        'actual_hours' => 1.75
    ]);

    expect($workItem->delegation_status)->toBe('assigned');
    expect($workItem->delegation_context)->toBe(['priority' => 'high']);
    expect((float) $workItem->estimated_hours)->toBe(2.5);
    expect((float) $workItem->actual_hours)->toBe(1.75);
});

test('task assignment creation and relationships', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);

    $agent = AgentProfile::create([
        'name' => 'Test Agent',
        'slug' => 'test-agent',
        'type' => 'backend-engineer',
        'mode' => 'implementation',
        'status' => 'active'
    ]);

    $workItem = WorkItem::create([
        'type' => 'task',
        'status' => 'todo'
    ]);

    $assignment = TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'agent_id' => $agent->id,
        'assigned_by' => $user->id,
        'assigned_at' => now(),
        'status' => 'assigned',
        'notes' => 'Test assignment',
        'context' => ['priority' => 'medium']
    ]);

    expect($assignment->work_item_id)->toBe($workItem->id);
    expect($assignment->agent_id)->toBe($agent->id);
    expect($assignment->assigned_by)->toBe($user->id);
    expect($assignment->status)->toBe('assigned');
    expect($assignment->context)->toBe(['priority' => 'medium']);

    // Test relationships
    expect($assignment->workItem->id)->toBe($workItem->id);
    expect($assignment->agent->name)->toBe($agent->name);
    expect($assignment->assignedBy->name)->toBe($user->name);
});

test('agent profile relationships', function () {
    $agent = AgentProfile::create([
        'name' => 'Test Agent',
        'slug' => 'test-agent',
        'type' => 'backend-engineer',
        'mode' => 'implementation',
        'status' => 'active'
    ]);

    $workItem = WorkItem::create([
        'type' => 'task',
        'status' => 'todo'
    ]);

    $assignment = TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'agent_id' => $agent->id,
        'assigned_at' => now(),
        'status' => 'assigned'
    ]);

    // Test that agent has assignments
    expect($agent->assignments()->count())->toBe(1);
    expect($agent->activeAssignments()->count())->toBe(1);

    // Mark assignment as completed
    $assignment->update(['status' => 'completed']);
    expect($agent->activeAssignments()->count())->toBe(0);
});

test('work item relationships', function () {
    $agent = AgentProfile::create([
        'name' => 'Test Agent',
        'slug' => 'test-agent',
        'type' => 'backend-engineer',
        'mode' => 'implementation',
        'status' => 'active'
    ]);

    $parentItem = WorkItem::create([
        'type' => 'epic',
        'status' => 'todo'
    ]);

    $childItem = WorkItem::create([
        'type' => 'task',
        'parent_id' => $parentItem->id,
        'status' => 'todo',
        'assignee_type' => 'agent',
        'assignee_id' => $agent->id
    ]);

    $assignment = TaskAssignment::create([
        'work_item_id' => $childItem->id,
        'agent_id' => $agent->id,
        'assigned_at' => now(),
        'status' => 'assigned'
    ]);

    // Test parent-child relationships
    expect($childItem->parent->id)->toBe($parentItem->id);
    expect($parentItem->children()->count())->toBe(1);

    // Test assignment relationships
    expect($childItem->assignments()->count())->toBe(1);
    expect($childItem->currentAssignment->id)->toBe($assignment->id);

    // Test agent assignment relationship
    expect($childItem->assignedAgent?->id)->toBe($agent->id);
});

test('scopes work correctly', function () {
    $activeAgent = AgentProfile::create([
        'name' => 'Active Agent',
        'slug' => 'active-agent',
        'type' => 'backend-engineer',
        'mode' => 'implementation',
        'status' => 'active'
    ]);

    $inactiveAgent = AgentProfile::create([
        'name' => 'Inactive Agent',
        'slug' => 'inactive-agent',
        'type' => 'frontend-engineer',
        'mode' => 'implementation',
        'status' => 'inactive'
    ]);

    // Test agent scopes
    expect(AgentProfile::active()->count())->toBe(1);
    expect(AgentProfile::byType('backend-engineer')->count())->toBe(1);

    $workItem = WorkItem::create([
        'type' => 'task',
        'status' => 'todo',
        'assignee_type' => 'agent',
        'assignee_id' => $activeAgent->id,
        'delegation_status' => 'unassigned'
    ]);

    // Test work item scopes
    expect(WorkItem::unassigned()->count())->toBe(1);
    expect(WorkItem::assignedToAgents()->count())->toBe(1);
    expect(WorkItem::byDelegationStatus('unassigned')->count())->toBe(1);
});
