<?php

use App\Contracts\ToolContract;
use App\Services\Telemetry\CorrelationContext;
use App\Services\Telemetry\ToolTelemetry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;

beforeEach(function () {
    // Clear correlation context between tests
    CorrelationContext::clear();

    // Clear static health status between tests
    $reflection = new ReflectionClass(ToolTelemetry::class);
    $healthProperty = $reflection->getProperty('healthStatus');
    $healthProperty->setAccessible(true);
    $healthProperty->setValue(null, []);
    $healthProperty->setAccessible(false);

    // Set up tool telemetry configuration for tests
    config([
        'tool-telemetry.enabled' => true,
        'tool-telemetry.performance.tool_thresholds' => [
            'fast' => 50,
            'normal' => 200,
            'slow' => 1000,
            'very_slow' => 3000,
        ],
        'tool-telemetry.performance.alert_thresholds' => [
            'slow_tool' => 3000,
            'memory_usage' => 128,
            'payload_size' => 10,
            'error_rate' => 0.05,
            'consecutive_failures' => 3,
        ],
        'tool-telemetry.performance.data_size_thresholds' => [
            'small_payload' => 1024,
            'medium_payload' => 102400,
            'large_payload' => 1048576,
        ],
        'tool-telemetry.alerts.conditions' => [
            'availability_issues' => true,
            'performance_degradation' => true,
        ],
        'tool-telemetry.sanitization.sensitive_patterns' => [
            '/password/i',
            '/secret/i',
            '/token/i',
        ],
        'tool-telemetry.sanitization.max_parameter_length' => 500,
        'tool-telemetry.sanitization.hash_sensitive_values' => true,
        'tool-telemetry.sampling.tool_execution' => 1.0,
        'tool-telemetry.features.correlation_tracking' => true,
        'tool-telemetry.health.enabled' => true,
    ]);

    // Mock the log channel to capture telemetry
    Log::shouldReceive('channel')->with('tool-telemetry')->andReturnSelf();
});

it('logs tool invocation start with telemetry', function () {
    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    $parameters = ['param1' => 'value1', 'param2' => 123];

    Log::shouldReceive('info')
        ->once()
        ->with('tool.invocation.started', Mockery::on(function ($data) {
            return isset($data['invocation_id']) &&
                   isset($data['tool_name']) &&
                   $data['tool_name'] === 'test.tool' &&
                   isset($data['parameters']) &&
                   isset($data['parameter_stats']);
        }));

    $invocationId = $telemetry->startInvocation($tool, $parameters);

    expect($invocationId)->not()->toBeEmpty();
    expect(ToolTelemetry::getActiveInvocations())->toHaveKey($invocationId);
});

it('logs tool invocation completion with telemetry', function () {
    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    // Mock start log
    Log::shouldReceive('info')->once()->with('tool.invocation.started', Mockery::any());

    $invocationId = $telemetry->startInvocation($tool, []);

    $result = ['status' => 'success', 'data' => ['item1', 'item2']];

    Log::shouldReceive('info')
        ->once()
        ->with('tool.invocation.completed', Mockery::on(function ($data) {
            return isset($data['invocation_id']) &&
                   isset($data['duration_ms']) &&
                   isset($data['success']) &&
                   $data['success'] === true &&
                   isset($data['performance_category']) &&
                   isset($data['result_stats']);
        }));

    // Health check log
    Log::shouldReceive('info')->once()->with('tool.health.check', Mockery::any());

    $telemetry->completeInvocation($invocationId, $result);

    expect(ToolTelemetry::getActiveInvocations())->not()->toHaveKey($invocationId);
});

it('logs tool invocation failure with telemetry', function () {
    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    // Mock start log
    Log::shouldReceive('info')->once()->with('tool.invocation.started', Mockery::any());

    $invocationId = $telemetry->startInvocation($tool, []);

    $errorMessage = 'Test error occurred';

    Log::shouldReceive('info')
        ->once()
        ->with('tool.invocation.failed', Mockery::on(function ($data) use ($errorMessage) {
            return isset($data['invocation_id']) &&
                   isset($data['success']) &&
                   $data['success'] === false &&
                   isset($data['error']) &&
                   $data['error'] === $errorMessage;
        }));

    // Health check log
    Log::shouldReceive('info')->once()->with('tool.health.check', Mockery::any());

    $telemetry->completeInvocation($invocationId, [], $errorMessage);
});

it('sanitizes sensitive parameters', function () {
    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    $parameters = [
        'username' => 'testuser',
        'password' => 'secret123',
        'api_token' => 'abc123def',
        'data' => 'normal data',
    ];

    Log::shouldReceive('info')
        ->once()
        ->with('tool.invocation.started', Mockery::on(function ($data) {
            $params = $data['parameters'] ?? [];

            return isset($params['username']) &&
                   $params['username'] === 'testuser' &&
                   isset($params['password']) &&
                   $params['password'] !== 'secret123' && // Should be hashed/redacted
                   isset($params['api_token']) &&
                   $params['api_token'] !== 'abc123def' && // Should be hashed/redacted
                   isset($params['data']) &&
                   $params['data'] === 'normal data'; // Should be unchanged
        }));

    $telemetry->startInvocation($tool, $parameters);
});

it('tracks tool chains with correlation context', function () {
    $correlationId = (string) Str::uuid();
    CorrelationContext::set($correlationId);

    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    Log::shouldReceive('info')
        ->once()
        ->with('tool.invocation.started', Mockery::on(function ($data) use ($correlationId) {
            return isset($data['correlation']) &&
                   $data['correlation']['correlation_id'] === $correlationId;
        }));

    $telemetry->startInvocation($tool, []);

    $chains = ToolTelemetry::getToolChains();
    expect($chains)->toHaveKey($correlationId);
    expect($chains[$correlationId]['tools'])->toHaveCount(1);
    expect($chains[$correlationId]['tools'][0]['tool_name'])->toBe('test.tool');
});

it('categorizes performance correctly', function () {
    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    // Mock start log
    Log::shouldReceive('info')->once()->with('tool.invocation.started', Mockery::any());

    $invocationId = $telemetry->startInvocation($tool, []);

    // Simulate slow operation
    usleep(250000); // 250ms - should be categorized as 'slow'

    Log::shouldReceive('info')
        ->once()
        ->with('tool.invocation.completed', Mockery::on(function ($data) {
            return isset($data['performance_category']) &&
                   in_array($data['performance_category'], ['normal', 'slow', 'very_slow', 'critical']);
        }));

    // Health check log
    Log::shouldReceive('info')->once()->with('tool.health.check', Mockery::any());

    $telemetry->completeInvocation($invocationId, []);
});

it('records health check results', function () {
    $telemetry = app(ToolTelemetry::class);

    Log::shouldReceive('info')
        ->once()
        ->with('tool.health.check', Mockery::on(function ($data) {
            return isset($data['tool_name']) &&
                   $data['tool_name'] === 'test.tool' &&
                   isset($data['healthy']) &&
                   $data['healthy'] === true &&
                   isset($data['response_time_ms']);
        }));

    // May also receive status change log
    Log::shouldReceive('info')->zeroOrMoreTimes()->with('tool.health.status_changed', Mockery::any());

    $telemetry->recordHealthCheck('test.tool', true, null, 100.5);

    $healthStatus = ToolTelemetry::getHealthStatus();
    expect($healthStatus)->toHaveKey('test.tool');
    expect($healthStatus['test.tool']['consecutive_successes'])->toBeGreaterThanOrEqual(1);
    expect($healthStatus['test.tool']['consecutive_failures'])->toBe(0);
});

it('tracks consecutive failures for health monitoring', function () {
    $telemetry = app(ToolTelemetry::class);

    Log::shouldReceive('info')->times(3)->with('tool.health.check', Mockery::any());
    Log::shouldReceive('info')->once()->with('tool.health.status_changed', Mockery::any());

    // Record 3 consecutive failures (should trigger status change)
    $telemetry->recordHealthCheck('test.tool', false, 'error 1', 100);
    $telemetry->recordHealthCheck('test.tool', false, 'error 2', 100);
    $telemetry->recordHealthCheck('test.tool', false, 'error 3', 100);

    $healthStatus = ToolTelemetry::getHealthStatus();
    expect($healthStatus['test.tool']['consecutive_failures'])->toBe(3);
    expect($healthStatus['test.tool']['current_status'])->toBe('unhealthy');
});

it('respects telemetry disabled configuration', function () {
    config(['tool-telemetry.enabled' => false]);

    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    // Should not log anything when disabled
    Log::shouldNotReceive('info');

    $invocationId = $telemetry->startInvocation($tool, []);
    expect($invocationId)->toBeEmpty();
});

it('respects sampling configuration', function () {
    config(['tool-telemetry.sampling.tool_execution' => 0.0]); // Never sample

    $telemetry = app(ToolTelemetry::class);
    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('test.tool');
    $tool->shouldReceive('scope')->andReturn('test.scope');

    // Mock may receive calls but sampling should prevent actual logging
    Log::shouldReceive('info')->never();

    $invocationId = $telemetry->startInvocation($tool, []);
    // Should still return an ID for tracking
    expect($invocationId)->not()->toBeEmpty();
});
