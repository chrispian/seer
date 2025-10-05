<?php

use App\Contracts\ToolContract;
use App\Services\Telemetry\ToolHealthMonitor;
use App\Services\Telemetry\ToolTelemetry;
use App\Support\ToolRegistry;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'tool-telemetry.health.enabled' => true,
        'tool-telemetry.health.health_check_timeout_ms' => 1000,
        'tool-telemetry.health.failure_threshold' => 3,
        'tool-telemetry.health.recovery_threshold' => 2,
    ]);

    Log::shouldReceive('channel')->with('tool-telemetry')->andReturnSelf();
});

it('performs health check on a tool', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('db.query');
    $tool->shouldReceive('inputSchema')->andReturn(['entity' => 'required']);
    $tool->shouldReceive('run')->with([
        'entity' => 'work_items',
        'limit' => 1,
        'offset' => 0,
    ])->andReturn(['items' => []]);

    $registry->shouldReceive('get')->with('db.query')->andReturn($tool);
    $telemetry->shouldReceive('recordHealthCheck')->once()->with(
        'db.query',
        true,
        null,
        Mockery::type('float')
    );

    $result = $monitor->checkTool('db.query');

    expect($result['status'])->toBe('healthy');
    expect($result)->toHaveKey('response_time_ms');
    expect($result)->toHaveKey('timestamp');
});

it('detects unhealthy tool', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('db.query');
    $tool->shouldReceive('inputSchema')->andReturn(['entity' => 'required']);
    $tool->shouldReceive('run')->andThrow(new Exception('Database connection failed'));

    $registry->shouldReceive('get')->with('db.query')->andReturn($tool);
    $telemetry->shouldReceive('recordHealthCheck')->once()->with(
        'db.query',
        false,
        'Health check failed: Database connection failed',
        Mockery::type('float')
    );

    $result = $monitor->checkTool('db.query');

    expect($result['status'])->toBe('unhealthy');
    expect($result['error'])->toContain('Database connection failed');
});

it('handles tool not found', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    $registry->shouldReceive('get')->with('nonexistent.tool')->andReturn(null);

    $result = $monitor->checkTool('nonexistent.tool');

    expect($result['status'])->toBe('not_found');
    expect($result['error'])->toBe('Tool not found in registry');
});

it('checks all tools and generates summary', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    // Mock tools
    $dbTool = Mockery::mock(ToolContract::class);
    $dbTool->shouldReceive('name')->andReturn('db.query');
    $dbTool->shouldReceive('inputSchema')->andReturn(['entity' => 'required']);
    $dbTool->shouldReceive('run')->andReturn(['items' => []]);

    $memoryTool = Mockery::mock(ToolContract::class);
    $memoryTool->shouldReceive('name')->andReturn('memory.search');
    $memoryTool->shouldReceive('inputSchema')->andReturn(['q' => 'string']);
    $memoryTool->shouldReceive('run')->andReturn(['items' => []]);

    $registry->shouldReceive('get')->with('db.query')->andReturn($dbTool);
    $registry->shouldReceive('get')->with('memory.search')->andReturn($memoryTool);
    $registry->shouldReceive('get')->with('memory.write')->andReturn(null);
    $registry->shouldReceive('get')->with('export.generate')->andReturn(null);

    $telemetry->shouldReceive('recordHealthCheck')->times(2);

    // Expect summary log
    Log::shouldReceive('info')->once()->with('tool.health.summary', Mockery::on(function ($data) {
        return isset($data['total_tools']) &&
               $data['total_tools'] === 4 &&
               isset($data['healthy_tools']) &&
               $data['healthy_tools'] === 2 &&
               isset($data['unhealthy_tools']) &&
               $data['unhealthy_tools'] === 2;
    }));

    $results = $monitor->checkAllTools();

    expect($results)->toHaveKey('db.query');
    expect($results)->toHaveKey('memory.search');
    expect($results)->toHaveKey('memory.write');
    expect($results)->toHaveKey('export.generate');

    expect($results['db.query']['status'])->toBe('healthy');
    expect($results['memory.search']['status'])->toBe('healthy');
    expect($results['memory.write']['status'])->toBe('not_found');
    expect($results['export.generate']['status'])->toBe('not_found');
});

it('generates health alert for poor system health', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    config(['tool-telemetry.alerts.enabled' => true]);

    // Mock all tools as unhealthy to trigger alert (0% health)
    $registry->shouldReceive('get')->andReturn(null);
    $telemetry->shouldReceive('recordHealthCheck')->never();

    // Expect summary log
    Log::shouldReceive('info')->once()->with('tool.health.summary', Mockery::any());

    // Expect health alert (health < 80%)
    Log::shouldReceive('warning')->once()->with('tool.health.alert', Mockery::on(function ($data) {
        return isset($data['alert_type']) &&
               $data['alert_type'] === 'poor_tool_health' &&
               isset($data['health_percentage']) &&
               $data['health_percentage'] < 80;
    }));

    $monitor->checkAllTools();
});

it('validates memory tool health', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('memory.search');
    $tool->shouldReceive('inputSchema')->andReturn(['q' => 'string']);
    $tool->shouldReceive('run')->with([
        'q' => 'test',
        'limit' => 1,
    ])->andReturn(['items' => []]);

    $registry->shouldReceive('get')->with('memory.search')->andReturn($tool);
    $telemetry->shouldReceive('recordHealthCheck')->once()->with(
        'memory.search',
        true,
        null,
        Mockery::type('float')
    );

    $result = $monitor->checkTool('memory.search');

    expect($result['status'])->toBe('healthy');
});

it('validates export tool health', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    config(['tools.allow_write_paths' => [storage_path('app/exports')]]);

    $tool = Mockery::mock(ToolContract::class);
    $tool->shouldReceive('name')->andReturn('export.generate');
    $tool->shouldReceive('inputSchema')->andReturn(['format' => 'string']);

    $registry->shouldReceive('get')->with('export.generate')->andReturn($tool);
    $telemetry->shouldReceive('recordHealthCheck')->once()->with(
        'export.generate',
        true,
        null,
        Mockery::type('float')
    );

    $result = $monitor->checkTool('export.generate');

    expect($result['status'])->toBe('healthy');
});

it('provides system health overview', function () {
    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    // Mock health status
    ToolTelemetry::$healthStatus = [
        'db.query' => ['current_status' => 'healthy'],
        'memory.search' => ['current_status' => 'healthy'],
        'memory.write' => ['current_status' => 'unhealthy'],
        'export.generate' => ['current_status' => 'healthy'],
    ];

    $health = $monitor->getSystemHealth();

    expect($health['overall_health'])->toBe(75.0); // 3 out of 4 healthy
    expect($health['total_tools'])->toBe(4);
    expect($health['healthy_tools'])->toBe(3);
    expect($health['tool_status'])->toHaveKey('db.query');
});

it('skips health checks when disabled', function () {
    config(['tool-telemetry.health.enabled' => false]);

    $registry = Mockery::mock(ToolRegistry::class);
    $telemetry = Mockery::mock(ToolTelemetry::class);
    $monitor = new ToolHealthMonitor($registry, $telemetry);

    $results = $monitor->checkAllTools();

    expect($results)->toBeEmpty();
});
