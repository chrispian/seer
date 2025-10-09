<?php

use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\RouterDecision;
use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;
use App\Services\Orchestration\ToolAware\DTOs\ToolResult;
use App\Services\Orchestration\ToolAware\DTOs\ExecutionTrace;
use App\Services\Orchestration\ToolAware\DTOs\OutcomeSummary;

it('context bundle constructs correctly', function () {
    $bundle = new ContextBundle(
        user_message: 'Test message',
        conversation_summary: 'Previous context',
        agent_prefs: ['mode' => 'fast'],
        tool_registry_preview: [['slug' => 'gmail']]
    );

    expect($bundle->user_message)->toBe('Test message');
    expect($bundle->conversation_summary)->toBe('Previous context');
    expect($bundle->agent_prefs)->toHaveKey('mode');
    expect($bundle->tool_registry_preview)->toHaveCount(1);
});

it('context bundle converts to array', function () {
    $bundle = new ContextBundle(
        user_message: 'Test',
        conversation_summary: 'Summary'
    );

    $array = $bundle->toArray();

    expect($array)->toHaveKeys(['user_message', 'conversation_summary', 'agent_prefs', 'tool_registry_preview']);
});

it('router decision creates from array', function () {
    $data = [
        'needs_tools' => true,
        'rationale' => 'Need calendar access',
        'high_level_goal' => 'fetch events',
    ];

    $decision = RouterDecision::fromArray($data);

    expect($decision->needs_tools)->toBeTrue();
    expect($decision->rationale)->toBe('Need calendar access');
    expect($decision->high_level_goal)->toBe('fetch events');
});

it('tool plan has helper methods', function () {
    $plan = new ToolPlan(
        selected_tool_ids: ['tool1', 'tool2'],
        plan_steps: [
            ['tool_id' => 'tool1', 'args' => [], 'why' => 'test'],
            ['tool_id' => 'tool2', 'args' => [], 'why' => 'test'],
        ]
    );

    expect($plan->hasSteps())->toBeTrue();
    expect($plan->stepCount())->toBe(2);
});

it('execution trace tracks errors', function () {
    $trace = new ExecutionTrace();

    $success = new ToolResult('tool1', [], ['data' => 'ok'], null, 100, true);
    $error = new ToolResult('tool2', [], null, 'Failed', 50, false);

    $trace->addStep($success);
    $trace->addStep($error);

    expect($trace->hasErrors())->toBeTrue();
    expect($trace->getErrors())->toHaveCount(1);
    expect($trace->total_elapsed_ms)->toBe(150.0);
});

it('execution trace generates correlation id', function () {
    $trace1 = new ExecutionTrace();
    $trace2 = new ExecutionTrace();

    expect($trace1->correlation_id)->not->toBe($trace2->correlation_id);
    expect($trace1->correlation_id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('outcome summary clamps confidence', function () {
    $summary1 = new OutcomeSummary('Test', [], [], 1.5);
    $summary2 = new OutcomeSummary('Test', [], [], -0.5);

    expect($summary1->confidence)->toBe(1.0);
    expect($summary2->confidence)->toBe(0.0);
});

it('outcome summary creates from array', function () {
    $data = [
        'short_summary' => 'Summary text',
        'key_facts' => ['fact1', 'fact2'],
        'links' => ['http://example.com'],
        'confidence' => 0.85,
    ];

    $summary = OutcomeSummary::fromArray($data);

    expect($summary->short_summary)->toBe('Summary text');
    expect($summary->key_facts)->toHaveCount(2);
    expect($summary->links)->toHaveCount(1);
    expect($summary->confidence)->toBe(0.85);
});
