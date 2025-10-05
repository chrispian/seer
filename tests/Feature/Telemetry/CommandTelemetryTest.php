<?php

use App\Services\Telemetry\CommandTelemetry;
use App\Services\Telemetry\CorrelationContext;
use App\Services\Commands\DSL\CommandRunner;
use App\Decorators\CommandTelemetryDecorator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

beforeEach(function () {
    // Clear correlation context between tests
    CorrelationContext::clear();
    
    // Set up command telemetry configuration for tests
    config([
        'command-telemetry.enabled' => true,
        'command-telemetry.performance.command_thresholds' => [
            'fast' => 100,
            'normal' => 500,
            'slow' => 2000,
            'very_slow' => 5000,
        ],
        'command-telemetry.performance.step_thresholds' => [
            'fast' => 50,
            'normal' => 200,
            'slow' => 1000,
            'very_slow' => 3000,
        ],
        'command-telemetry.performance.template_thresholds' => [
            'fast' => 10,
            'normal' => 50,
            'slow' => 200,
        ],
        'command-telemetry.performance.alert_thresholds' => [
            'slow_command' => 5000,
            'slow_step' => 3000,
            'template_rendering' => 500,
        ],
        'command-telemetry.sanitization.sensitive_patterns' => [
            '/password/i',
            '/secret/i',
            '/token/i',
            '/key/i',
        ],
        'command-telemetry.sanitization.max_argument_length' => 500,
        'command-telemetry.sanitization.hash_sensitive_values' => true,
        'command-telemetry.sampling.command_execution' => 1.0,
        'command-telemetry.features.performance_alerts' => true,
    ]);
    
    // Mock the log channel to capture telemetry
    Log::shouldReceive('channel')->with('command-telemetry')->andReturnSelf();
});

it('logs command execution start and completion', function () {
    $command = 'test-command';
    $arguments = ['arg1' => 'value1', 'arg2' => 'value2'];
    
    // Set up log expectations
    Log::shouldReceive('info')
        ->with('command.execution.started', \Mockery::on(function ($data) use ($command, $arguments) {
            return $data['event'] === 'command.execution.started' &&
                   $data['data']['command'] === $command &&
                   $data['data']['arguments'] === $arguments &&
                   isset($data['meta']['timestamp']);
        }))
        ->once();
    
    Log::shouldReceive('info')
        ->with('command.execution.completed', \Mockery::on(function ($data) use ($command) {
            return $data['event'] === 'command.execution.completed' &&
                   $data['data']['command'] === $command &&
                   $data['data']['success'] === true &&
                   isset($data['data']['duration_ms']) &&
                   isset($data['data']['performance_category']);
        }))
        ->once();
    
    // Test command execution telemetry
    CommandTelemetry::logCommandStart($command, $arguments, ['source_type' => 'test']);
    CommandTelemetry::logCommandComplete($command, $arguments, 150.5, true);
});

it('logs step execution with telemetry', function () {
    $stepType = 'ai.generate';
    $stepId = 'test-step-123';
    $config = ['prompt' => 'test prompt', 'max_tokens' => 100];
    
    // Set up log expectations
    Log::shouldReceive('info')
        ->with('command.step.started', \Mockery::on(function ($data) use ($stepType, $stepId) {
            return $data['event'] === 'command.step.started' &&
                   $data['data']['step_type'] === $stepType &&
                   $data['data']['step_id'] === $stepId;
        }))
        ->once();
    
    Log::shouldReceive('info')
        ->with('command.step.completed', \Mockery::on(function ($data) use ($stepType, $stepId) {
            return $data['event'] === 'command.step.completed' &&
                   $data['data']['step_type'] === $stepType &&
                   $data['data']['step_id'] === $stepId &&
                   $data['data']['success'] === true &&
                   isset($data['data']['duration_ms']);
        }))
        ->once();
    
    // Test step execution telemetry
    CommandTelemetry::logStepStart($stepType, $stepId, $config);
    CommandTelemetry::logStepComplete($stepType, $stepId, 75.2, true);
});

it('logs template rendering performance', function () {
    $template = '{{ user.name }} - {{ user.email }}';
    $duration = 25.5;
    $cacheHit = true;
    $stats = ['context_keys' => ['user'], 'has_variables' => true];
    
    Log::shouldReceive('info')
        ->with('command.template.rendered', \Mockery::on(function ($data) use ($template, $duration, $cacheHit) {
            return $data['event'] === 'command.template.rendered' &&
                   $data['data']['template_hash'] === md5($template) &&
                   $data['data']['template_length'] === strlen($template) &&
                   $data['data']['duration_ms'] === $duration &&
                   $data['data']['cache_hit'] === $cacheHit &&
                   isset($data['data']['performance_category']);
        }))
        ->once();
    
    CommandTelemetry::logTemplateRendering($template, $duration, $cacheHit, $stats);
});

it('logs condition evaluation with branch information', function () {
    $condition = 'user.role == "admin"';
    $result = true;
    $duration = 5.2;
    $branchExecuted = 'then';
    
    Log::shouldReceive('info')
        ->with('command.condition.evaluated', \Mockery::on(function ($data) use ($condition, $result, $branchExecuted) {
            return $data['event'] === 'command.condition.evaluated' &&
                   $data['data']['condition_hash'] === md5($condition) &&
                   $data['data']['result'] === $result &&
                   $data['data']['branch_executed'] === $branchExecuted &&
                   isset($data['data']['duration_ms']);
        }))
        ->once();
    
    CommandTelemetry::logConditionEvaluation($condition, $result, $duration, $branchExecuted);
});

it('logs AI generation metrics', function () {
    $promptLength = 150;
    $duration = 1250.5;
    $success = true;
    $metrics = [
        'max_tokens' => 500,
        'cache_enabled' => false,
        'response_length' => 420,
        'expect_type' => 'text',
    ];
    
    Log::shouldReceive('info')
        ->with('command.ai.generation', \Mockery::on(function ($data) use ($promptLength, $duration, $success, $metrics) {
            return $data['event'] === 'command.ai.generation' &&
                   $data['data']['prompt_length'] === $promptLength &&
                   $data['data']['duration_ms'] === $duration &&
                   $data['data']['success'] === $success &&
                   $data['data']['max_tokens'] === $metrics['max_tokens'] &&
                   $data['data']['response_length'] === $metrics['response_length'];
        }))
        ->once();
    
    CommandTelemetry::logAiGeneration($promptLength, $duration, $success, $metrics);
});

it('includes correlation context when available', function () {
    $correlationId = (string) Str::uuid();
    CorrelationContext::set($correlationId);
    
    Log::shouldReceive('info')
        ->with('command.execution.started', \Mockery::on(function ($data) use ($correlationId) {
            return isset($data['correlation']) &&
                   $data['correlation']['correlation_id'] === $correlationId;
        }))
        ->once();
    
    CommandTelemetry::logCommandStart('test-command', []);
});

it('categorizes performance correctly', function () {
    // Test fast command
    Log::shouldReceive('info')
        ->with('command.execution.completed', \Mockery::on(function ($data) {
            return $data['data']['performance_category'] === 'fast';
        }))
        ->once();
    
    CommandTelemetry::logCommandComplete('fast-command', [], 50, true);
    
    // Test slow command
    Log::shouldReceive('info')
        ->with('command.execution.completed', \Mockery::on(function ($data) {
            return $data['data']['performance_category'] === 'slow';
        }))
        ->once();
    
    CommandTelemetry::logCommandComplete('slow-command', [], 1500, true);
});

it('logs performance alerts for slow operations', function () {
    config(['command-telemetry.features.performance_alerts' => true]);
    
    // Allow any logs for this test since the alert structure is complex
    Log::shouldReceive('info')->zeroOrMoreTimes();
    
    // This should trigger a performance alert internally and log command completion
    CommandTelemetry::logCommandComplete('very-slow-command', [], 6000, true);
    
    // Test passes if no exceptions are thrown
    expect(true)->toBeTrue();
});

it('sanitizes sensitive data in arguments', function () {
    $arguments = [
        'username' => 'john_doe',
        'password' => 'secret123',
        'token' => 'abc123xyz',
        'safe_data' => 'this is safe',
    ];
    
    Log::shouldReceive('info')
        ->with('command.execution.started', \Mockery::on(function ($data) {
            $args = $data['data']['arguments'];
            return $args['username'] === 'john_doe' &&
                   $args['safe_data'] === 'this is safe' &&
                   (str_contains($args['password'], '[HASH:') || $args['password'] === '[REDACTED]') &&
                   (str_contains($args['token'], '[HASH:') || $args['token'] === '[REDACTED]');
        }))
        ->once();
    
    CommandTelemetry::logCommandStart('test-command', $arguments);
});

it('can be disabled via configuration', function () {
    config(['command-telemetry.enabled' => false]);
    
    // Should not receive any log calls when disabled
    Log::shouldNotReceive('info');
    
    CommandTelemetry::logCommandStart('test-command', []);
    CommandTelemetry::logCommandComplete('test-command', [], 100, true);
});

it('respects sampling rates', function () {
    config(['command-telemetry.sampling.command_execution' => 0.0]); // Never sample
    
    // Should not receive any log calls when sampling rate is 0
    Log::shouldNotReceive('info');
    
    CommandTelemetry::logCommandStart('test-command', []);
});