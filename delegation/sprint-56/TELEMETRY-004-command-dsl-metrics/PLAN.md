# TELEMETRY-004: Command & DSL Execution Metrics - Implementation Plan

## Estimated Time: 8 hours

## Phase 1: Command Telemetry Service (2 hours)

### 1.1 Create Command Telemetry Helper
**File**: `app/Services/Telemetry/CommandTelemetry.php`

```php
<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommandTelemetry
{
    public static function executionStarted(array $context): string
    {
        $executionId = (string) Str::uuid();
        
        Log::info('command.execution.started', array_filter([
            'execution_id' => $executionId,
            'command_slug' => $context['command_slug'] ?? null,
            'dry_run' => $context['dry_run'] ?? false,
            'request_source' => $context['request_source'] ?? 'unknown',
            'user_id' => 'local-default'
        ]));
        
        return $executionId;
    }
    
    public static function executionCompleted(array $context): void
    {
        Log::info('command.execution.completed', array_filter([
            'execution_id' => $context['execution_id'] ?? null,
            'command_slug' => $context['command_slug'] ?? null,
            'total_duration_ms' => $context['duration_ms'] ?? null,
            'steps_executed' => $context['steps_executed'] ?? null,
            'steps_successful' => $context['steps_successful'] ?? null,
            'steps_failed' => $context['steps_failed'] ?? null,
            'dry_run' => $context['dry_run'] ?? false,
            'outcome' => $context['outcome'] ?? 'success',
            'user_id' => 'local-default'
        ]));
    }
    
    public static function executionFailed(array $context): void
    {
        Log::error('command.execution.failed', array_filter([
            'execution_id' => $context['execution_id'] ?? null,
            'command_slug' => $context['command_slug'] ?? null,
            'duration_ms' => $context['duration_ms'] ?? null,
            'error' => $context['error'] ?? null,
            'steps_completed' => $context['steps_completed'] ?? null,
            'dry_run' => $context['dry_run'] ?? false,
            'user_id' => 'local-default'
        ]));
    }
    
    public static function stepExecuted(array $context): void
    {
        Log::info('dsl.step.executed', array_filter([
            'execution_id' => $context['execution_id'] ?? null,
            'step_id' => $context['step_id'] ?? (string) Str::uuid(),
            'step_type' => $context['step_type'] ?? null,
            'command_slug' => $context['command_slug'] ?? null,
            'fragment_id' => $context['fragment_id'] ?? null,
            'duration_ms' => $context['duration_ms'] ?? null,
            'mutations_count' => $context['mutations_count'] ?? null,
            'dry_run' => $context['dry_run'] ?? false,
            'outcome' => $context['outcome'] ?? 'success',
            'user_id' => 'local-default'
        ]));
    }
    
    public static function stepFailed(array $context): void
    {
        Log::error('dsl.step.failed', array_filter([
            'execution_id' => $context['execution_id'] ?? null,
            'step_id' => $context['step_id'] ?? null,
            'step_type' => $context['step_type'] ?? null,
            'command_slug' => $context['command_slug'] ?? null,
            'duration_ms' => $context['duration_ms'] ?? null,
            'error' => $context['error'] ?? null,
            'dry_run' => $context['dry_run'] ?? false,
            'user_id' => 'local-default'
        ]));
    }
}
```

## Phase 2: CommandController Instrumentation (2 hours)

### 2.1 Enhance CommandController with Telemetry
**File**: `app/Http/Controllers/CommandController.php`

```php
use App\Services\Telemetry\CommandTelemetry;

// Around line 50-60 in execute method
public function execute(Request $request)
{
    $startTime = microtime(true);
    
    // Enhanced validation with telemetry
    try {
        $data = $request->validate([
            'command' => 'required|string',
            'dry_run' => 'boolean'
        ]);
    } catch (ValidationException $e) {
        Log::warning('command.validation.failed', [
            'errors' => array_keys($e->errors()),
            'user_id' => 'local-default'
        ]);
        throw $e;
    }
    
    $commandName = $data['command'];
    $isDryRun = $data['dry_run'] ?? false;
    
    // Start execution telemetry
    $executionId = CommandTelemetry::executionStarted([
        'command_slug' => $commandName,
        'dry_run' => $isDryRun,
        'request_source' => 'api'
    ]);
    
    try {
        $commandRunner = app(\App\Services\Commands\DSL\CommandRunner::class);
        
        $result = $commandRunner->run($commandName, [
            'execution_id' => $executionId,
            'dry_run' => $isDryRun,
            'request_data' => $request->all()
        ]);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        // Enhanced completion telemetry
        CommandTelemetry::executionCompleted([
            'execution_id' => $executionId,
            'command_slug' => $commandName,
            'duration_ms' => round($duration, 2),
            'steps_executed' => $result['steps_executed'] ?? null,
            'steps_successful' => $result['steps_successful'] ?? null,
            'steps_failed' => $result['steps_failed'] ?? 0,
            'dry_run' => $isDryRun,
            'outcome' => 'success'
        ]);
        
        // Enhanced logging for command execution success
        $this->logCommandExecution($executionId, $commandName, $result, $isDryRun);
        
        return response()->json([
            'success' => true,
            'execution_id' => $executionId,
            'result' => $result
        ]);
        
    } catch (\Exception $e) {
        $duration = (microtime(true) - $startTime) * 1000;
        
        CommandTelemetry::executionFailed([
            'execution_id' => $executionId,
            'command_slug' => $commandName,
            'duration_ms' => round($duration, 2),
            'error' => $e->getMessage(),
            'dry_run' => $isDryRun
        ]);
        
        // Keep existing error logging with enhanced context
        \Log::error('Command execution failed', [
            'execution_id' => $executionId,
            'command' => $commandName,
            'error' => $e->getMessage(),
            'dry_run' => $isDryRun,
            'user_id' => 'local-default'
        ]);
        
        throw $e;
    }
}

private function logCommandExecution(string $executionId, string $commandName, array $result, bool $isDryRun): void
{
    try {
        DB::table('command_executions')->insert([
            'id' => $executionId,
            'command_name' => $commandName,
            'executed_at' => now(),
            'dry_run' => $isDryRun,
            'result' => json_encode($result),
            'user_id' => auth()->id() ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } catch (\Exception $e) {
        // Enhanced failure logging
        \Log::warning('Failed to log command execution', [
            'execution_id' => $executionId,
            'command' => $commandName,
            'error' => $e->getMessage()
        ]);
    }
}
```

## Phase 3: DSL CommandRunner Enhancement (2 hours)

### 3.1 Update CommandRunner with Step Telemetry
**File**: `app/Services/Commands/DSL/CommandRunner.php`

```php
use App\Services\Telemetry\CommandTelemetry;

public function run(string $commandName, array $context = []): array
{
    $executionId = $context['execution_id'] ?? (string) Str::uuid();
    $isDryRun = $context['dry_run'] ?? false;
    $startTime = microtime(true);
    
    $stepStats = [
        'executed' => 0,
        'successful' => 0,
        'failed' => 0
    ];
    
    try {
        // ... existing command loading logic ...
        
        $result = $this->executeSteps($command['steps'], [
            'execution_id' => $executionId,
            'command_slug' => $commandName,
            'dry_run' => $isDryRun
        ] + $context, $stepStats);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        // Enhanced slow command logging
        if ($duration > 5000) {
            \Log::info('Slow Command Execution', [
                'execution_id' => $executionId,
                'command' => $commandName,
                'duration_ms' => round($duration, 2),
                'steps_executed' => $stepStats['executed'],
                'dry_run' => $isDryRun
            ]);
        }
        
        return array_merge($result, [
            'steps_executed' => $stepStats['executed'],
            'steps_successful' => $stepStats['successful'],
            'steps_failed' => $stepStats['failed']
        ]);
        
    } catch (\Exception $e) {
        // Log step statistics even on failure
        $duration = (microtime(true) - $startTime) * 1000;
        
        \Log::error('Command runner failed', [
            'execution_id' => $executionId,
            'command' => $commandName,
            'duration_ms' => round($duration, 2),
            'steps_completed' => $stepStats['executed'],
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}

private function executeSteps(array $steps, array $context, array &$stepStats): array
{
    $result = [];
    
    foreach ($steps as $step) {
        $stepStats['executed']++;
        $stepStartTime = microtime(true);
        
        try {
            $stepResult = $this->executeStep($step, $context);
            $stepDuration = (microtime(true) - $stepStartTime) * 1000;
            
            $stepStats['successful']++;
            
            // Log successful step execution
            CommandTelemetry::stepExecuted([
                'execution_id' => $context['execution_id'],
                'step_type' => $step['type'] ?? 'unknown',
                'command_slug' => $context['command_slug'],
                'fragment_id' => $this->extractFragmentId($stepResult, $step),
                'duration_ms' => round($stepDuration, 2),
                'mutations_count' => $this->extractMutationsCount($stepResult, $step),
                'dry_run' => $context['dry_run'] ?? false,
                'outcome' => 'success'
            ]);
            
            $result[] = $stepResult;
            
        } catch (\Exception $e) {
            $stepDuration = (microtime(true) - $stepStartTime) * 1000;
            $stepStats['failed']++;
            
            CommandTelemetry::stepFailed([
                'execution_id' => $context['execution_id'],
                'step_type' => $step['type'] ?? 'unknown',
                'command_slug' => $context['command_slug'],
                'duration_ms' => round($stepDuration, 2),
                'error' => $e->getMessage(),
                'dry_run' => $context['dry_run'] ?? false
            ]);
            
            throw $e;
        }
    }
    
    return $result;
}

private function extractFragmentId($stepResult, array $step): ?int
{
    // Extract fragment ID from step result based on step type
    if (isset($step['type'])) {
        switch ($step['type']) {
            case 'fragment_update':
            case 'fragment_create':
                return $stepResult['fragment_id'] ?? null;
            default:
                return null;
        }
    }
    return null;
}

private function extractMutationsCount($stepResult, array $step): ?int
{
    // Extract mutation count based on step type
    if (isset($step['type'])) {
        switch ($step['type']) {
            case 'database_update':
                return $stepResult['affected_rows'] ?? null;
            case 'fragment_update':
                return $stepResult['updated_fields'] ?? null;
            default:
                return null;
        }
    }
    return null;
}
```

## Phase 4: DSL Step Instrumentation (2.5 hours)

### 4.1 Create DSL Step Telemetry Trait
**File**: `app/Services/Commands/DSL/Steps/HasStepTelemetry.php`

```php
<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\Telemetry\CommandTelemetry;

trait HasStepTelemetry
{
    protected function logStepExecution(array $context, callable $step): mixed
    {
        $startTime = microtime(true);
        $stepType = class_basename($this);
        
        try {
            $result = $step();
            $duration = (microtime(true) - $startTime) * 1000;
            
            CommandTelemetry::stepExecuted([
                'execution_id' => $context['execution_id'] ?? null,
                'step_type' => $stepType,
                'command_slug' => $context['command_slug'] ?? null,
                'fragment_id' => $this->extractFragmentId($result),
                'duration_ms' => round($duration, 2),
                'mutations_count' => $this->extractMutationsCount($result),
                'dry_run' => $context['dry_run'] ?? false,
                'outcome' => 'success'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            CommandTelemetry::stepFailed([
                'execution_id' => $context['execution_id'] ?? null,
                'step_type' => $stepType,
                'command_slug' => $context['command_slug'] ?? null,
                'duration_ms' => round($duration, 2),
                'error' => $e->getMessage(),
                'dry_run' => $context['dry_run'] ?? false
            ]);
            
            throw $e;
        }
    }
    
    protected function extractFragmentId($result): ?int
    {
        // Override in specific steps
        return null;
    }
    
    protected function extractMutationsCount($result): ?int
    {
        // Override in specific steps  
        return null;
    }
}
```

### 4.2 Update Key DSL Steps with Telemetry
**Files**: Various DSL step files

```php
// DatabaseUpdateStep
use App\Services\Commands\DSL\Steps\HasStepTelemetry;

class DatabaseUpdateStep
{
    use HasStepTelemetry;
    
    public function execute(array $context): array
    {
        return $this->logStepExecution($context, function () use ($context) {
            // existing logic...
            return ['affected_rows' => $affectedRows];
        });
    }
    
    protected function extractMutationsCount($result): ?int
    {
        return $result['affected_rows'] ?? null;
    }
}

// FragmentUpdateStep  
class FragmentUpdateStep
{
    use HasStepTelemetry;
    
    public function execute(array $context): array
    {
        return $this->logStepExecution($context, function () use ($context) {
            // existing logic...
            return ['fragment_id' => $fragment->id, 'updated_fields' => count($updates)];
        });
    }
    
    protected function extractFragmentId($result): ?int
    {
        return $result['fragment_id'] ?? null;
    }
    
    protected function extractMutationsCount($result): ?int
    {
        return $result['updated_fields'] ?? null;
    }
}
```

## Phase 5: Testing & Validation (1.5 hours)

### 5.1 Unit Tests for Command Telemetry
**File**: `tests/Unit/Services/Telemetry/CommandTelemetryTest.php`

```php
<?php

namespace Tests\Unit\Services\Telemetry;

use App\Services\Telemetry\CommandTelemetry;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CommandTelemetryTest extends TestCase
{
    public function test_execution_started_logging()
    {
        Log::spy();
        
        $executionId = CommandTelemetry::executionStarted([
            'command_slug' => 'test.command',
            'dry_run' => true,
            'request_source' => 'api'
        ]);
        
        $this->assertIsString($executionId);
        
        Log::shouldHaveReceived('info')
            ->with('command.execution.started', \Mockery::on(function ($context) use ($executionId) {
                return $context['execution_id'] === $executionId &&
                       $context['command_slug'] === 'test.command' &&
                       $context['dry_run'] === true;
            }));
    }
    
    public function test_step_executed_logging()
    {
        Log::spy();
        
        CommandTelemetry::stepExecuted([
            'execution_id' => 'test-exec-id',
            'step_type' => 'DatabaseUpdateStep',
            'duration_ms' => 25.5,
            'mutations_count' => 3
        ]);
        
        Log::shouldHaveReceived('info')
            ->with('dsl.step.executed', \Mockery::on(function ($context) {
                return $context['step_type'] === 'DatabaseUpdateStep' &&
                       $context['duration_ms'] === 25.5 &&
                       $context['mutations_count'] === 3;
            }));
    }
}
```

### 5.2 Integration Tests for Command Execution
**File**: `tests/Feature/Telemetry/CommandExecutionTelemetryTest.php`

```php
<?php

namespace Tests\Feature\Telemetry;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CommandExecutionTelemetryTest extends TestCase
{
    public function test_command_execution_telemetry_flow()
    {
        Log::spy();
        
        $response = $this->postJson('/api/commands/execute', [
            'command' => 'test.simple.command',
            'dry_run' => false
        ]);
        
        $response->assertOk();
        
        // Verify execution started
        Log::shouldHaveReceived('info')
            ->with('command.execution.started', \Mockery::on(function ($context) {
                return $context['command_slug'] === 'test.simple.command' &&
                       isset($context['execution_id']);
            }));
            
        // Verify execution completed
        Log::shouldHaveReceived('info')
            ->with('command.execution.completed', \Mockery::on(function ($context) {
                return $context['command_slug'] === 'test.simple.command' &&
                       $context['outcome'] === 'success';
            }));
    }
}
```

## Implementation Checklist

- [ ] Create `CommandTelemetry` helper service
- [ ] Enhance `CommandController` with execution telemetry
- [ ] Update `CommandRunner` with step instrumentation  
- [ ] Create `HasStepTelemetry` trait for DSL steps
- [ ] Update key DSL steps with telemetry
- [ ] Unit tests for command telemetry
- [ ] Integration tests for command execution flow
- [ ] Performance tests for telemetry overhead

## Success Metrics

- Command executions logged with complete metadata
- DSL step performance tracked individually  
- Command success/failure rates measurable
- <3ms total telemetry overhead per command
- Integration with correlation IDs from TELEMETRY-001