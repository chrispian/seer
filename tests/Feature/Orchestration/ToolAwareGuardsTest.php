<?php

use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;
use App\Services\Orchestration\ToolAware\Guards\PermissionGate;
use App\Services\Orchestration\ToolAware\Guards\RateLimiter;
use App\Services\Orchestration\ToolAware\Guards\Redactor;
use App\Services\Orchestration\ToolAware\Guards\StepLimiter;

it('permission gate filters blocked tools', function () {
    config(['fragments.tools.allowed' => ['gmail.list', 'calendar.*']]);

    $plan = new ToolPlan(
        selected_tool_ids: ['gmail.list', 'gmail.send', 'calendar.listEvents'],
        plan_steps: [
            ['tool_id' => 'gmail.list', 'args' => [], 'why' => 'test'],
            ['tool_id' => 'gmail.send', 'args' => [], 'why' => 'test'],
            ['tool_id' => 'calendar.listEvents', 'args' => [], 'why' => 'test'],
        ]
    );

    $gate = new PermissionGate;
    $filtered = $gate->filter($plan, 1);

    expect($filtered->plan_steps)->toHaveCount(2);
    expect($filtered->selected_tool_ids)->toContain('gmail.list');
    expect($filtered->selected_tool_ids)->toContain('calendar.listEvents');
    expect($filtered->selected_tool_ids)->not->toContain('gmail.send');
});

it('permission gate allows all when no restrictions', function () {
    config(['fragments.tools.allowed' => []]);

    $plan = new ToolPlan(
        selected_tool_ids: ['gmail.send'],
        plan_steps: [
            ['tool_id' => 'gmail.send', 'args' => [], 'why' => 'test'],
        ]
    );

    $gate = new PermissionGate;
    $filtered = $gate->filter($plan);

    expect($filtered->plan_steps)->toHaveCount(1);
});

it('permission gate supports wildcard patterns', function () {
    config(['fragments.tools.allowed' => ['gmail.*']]);

    $plan = new ToolPlan(
        selected_tool_ids: ['gmail.list', 'gmail.send', 'calendar.list'],
        plan_steps: [
            ['tool_id' => 'gmail.list', 'args' => [], 'why' => 'test'],
            ['tool_id' => 'gmail.send', 'args' => [], 'why' => 'test'],
            ['tool_id' => 'calendar.list', 'args' => [], 'why' => 'test'],
        ]
    );

    $gate = new PermissionGate;
    $filtered = $gate->filter($plan);

    expect($filtered->plan_steps)->toHaveCount(2);
    expect($filtered->selected_tool_ids)->toContain('gmail.list');
    expect($filtered->selected_tool_ids)->toContain('gmail.send');
});

it('step limiter truncates excessive steps', function () {
    config(['fragments.tool_aware_turn.limits.max_steps_per_turn' => 3]);

    $plan = new ToolPlan(
        selected_tool_ids: ['tool1', 'tool2', 'tool3', 'tool4', 'tool5'],
        plan_steps: [
            ['tool_id' => 'tool1', 'args' => [], 'why' => '1'],
            ['tool_id' => 'tool2', 'args' => [], 'why' => '2'],
            ['tool_id' => 'tool3', 'args' => [], 'why' => '3'],
            ['tool_id' => 'tool4', 'args' => [], 'why' => '4'],
            ['tool_id' => 'tool5', 'args' => [], 'why' => '5'],
        ]
    );

    $limiter = new StepLimiter;
    $limited = $limiter->limit($plan);

    expect($limited->plan_steps)->toHaveCount(3);
    expect($limited->stepCount())->toBe(3);
});

it('step limiter allows plans within limit', function () {
    config(['fragments.tool_aware_turn.limits.max_steps_per_turn' => 10]);

    $plan = new ToolPlan(
        selected_tool_ids: ['tool1', 'tool2'],
        plan_steps: [
            ['tool_id' => 'tool1', 'args' => [], 'why' => '1'],
            ['tool_id' => 'tool2', 'args' => [], 'why' => '2'],
        ]
    );

    $limiter = new StepLimiter;
    $limited = $limiter->limit($plan);

    expect($limited->plan_steps)->toHaveCount(2);
    expect($limiter->isWithinLimit($limited))->toBeTrue();
});

it('redactor removes email addresses', function () {
    $redactor = new Redactor;

    $text = 'Contact me at john.doe@example.com or jane@test.org';
    $redacted = $redactor->redact($text);

    expect($redacted)->not->toContain('john.doe@example.com');
    expect($redacted)->not->toContain('jane@test.org');
    expect($redacted)->toContain('[REDACTED]');
});

it('redactor removes API keys', function () {
    $redactor = new Redactor;

    $text = 'API key: sk-1234567890abcdefghijklmnop';
    $redacted = $redactor->redact($text);

    expect($redacted)->not->toContain('sk-1234567890abcdefghijklmnop');
    expect($redacted)->toContain('[REDACTED]');
});

it('redactor handles arrays recursively', function () {
    $redactor = new Redactor;

    $data = [
        'email' => 'test@example.com',
        'nested' => [
            'api_key' => 'sk-12345678901234567890',
        ],
    ];

    $redacted = $redactor->redactArray($data);

    expect($redacted['email'])->not->toContain('test@example.com');
    expect($redacted['nested']['api_key'])->not->toContain('sk-12345678901234567890');
});

it('redactor removes sensitive keys', function () {
    $redactor = new Redactor;

    $data = [
        'username' => 'john',
        'password' => 'secret123',
        'api_key' => 'abc123',
        'public_data' => 'visible',
    ];

    $redacted = $redactor->redactKeys($data);

    expect($redacted['username'])->toBe('john');
    expect($redacted['public_data'])->toBe('visible');
    expect($redacted['password'])->toBe('[REDACTED]');
    expect($redacted['api_key'])->toBe('[REDACTED]');
});

it('rate limiter allows requests within limit', function () {
    $limiter = new RateLimiter;

    expect($limiter->allow('user123', 'gmail.list'))->toBeTrue();
});

it('rate limiter calculates exponential backoff', function () {
    $limiter = new RateLimiter;

    expect($limiter->backoffTime(0))->toBe(1);
    expect($limiter->backoffTime(1))->toBe(2);
    expect($limiter->backoffTime(2))->toBe(4);
    expect($limiter->backoffTime(3))->toBe(8);
    expect($limiter->backoffTime(10))->toBe(60); // Max 60 seconds
});

it('rate limiter identifies retryable status codes', function () {
    $limiter = new RateLimiter;

    expect($limiter->shouldRetry(429, 1))->toBeTrue();
    expect($limiter->shouldRetry(500, 1))->toBeTrue();
    expect($limiter->shouldRetry(503, 1))->toBeTrue();
    expect($limiter->shouldRetry(400, 1))->toBeFalse();
    expect($limiter->shouldRetry(404, 1))->toBeFalse();
    expect($limiter->shouldRetry(429, 5))->toBeFalse(); // Exceeded max retries
});
