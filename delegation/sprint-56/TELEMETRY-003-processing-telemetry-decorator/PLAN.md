# TELEMETRY-003: Fragment Processing Telemetry Decorator - Implementation Plan

## Estimated Time: 10 hours

## Phase 1: Telemetry Decorator Core (3 hours)

### 1.1 Create Processing Telemetry Decorator
**File**: `app/Services/Telemetry/ProcessingTelemetryDecorator.php`

```php
<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessingTelemetryDecorator
{
    public function wrapStep(string $stepName, callable $step, array $context = []): mixed
    {
        $stepId = (string) Str::uuid();
        $startTime = microtime(true);
        $fragmentId = $context['fragment_id'] ?? null;
        $jobId = $context['job_id'] ?? null;
        
        // Log step start
        Log::info('processing.step.started', [
            'step_id' => $stepId,
            'step' => $stepName,
            'job_id' => $jobId,
            'fragment_id' => $fragmentId,
            'user_id' => 'local-default'
        ]);
        
        try {
            $result = $step();
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Extract generated keys from result
            $generatedKeys = $this->extractGeneratedKeys($result, $stepName);
            
            Log::info('processing.step.completed', [
                'step_id' => $stepId,
                'step' => $stepName,
                'job_id' => $jobId,
                'fragment_id' => $fragmentId,
                'duration_ms' => round($duration, 2),
                'outcome' => 'success',
                'generated_keys' => $generatedKeys,
                'user_id' => 'local-default'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('processing.step.failed', [
                'step_id' => $stepId,
                'step' => $stepName,
                'job_id' => $jobId,
                'fragment_id' => $fragmentId,
                'duration_ms' => round($duration, 2),
                'outcome' => 'error',
                'error' => $e->getMessage(),
                'error_code' => $this->getErrorCode($e),
                'user_id' => 'local-default'
            ]);
            
            throw $e;
        }
    }
    
    public function wrapJob(string $jobName, callable $job, array $context = []): mixed
    {
        $jobId = (string) Str::uuid();
        $startTime = microtime(true);
        
        Log::info('processing.job.started', [
            'job_id' => $jobId,
            'job' => $jobName,
            'fragment_id' => $context['fragment_id'] ?? null,
            'processing_type' => $context['processing_type'] ?? 'unknown',
            'user_id' => 'local-default'
        ]);
        
        try {
            $result = $job(['job_id' => $jobId] + $context);
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('processing.job.completed', [
                'job_id' => $jobId,
                'job' => $jobName,
                'fragment_id' => $context['fragment_id'] ?? null,
                'duration_ms' => round($duration, 2),
                'outcome' => 'success',
                'user_id' => 'local-default'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('processing.job.failed', [
                'job_id' => $jobId,
                'job' => $jobName,
                'fragment_id' => $context['fragment_id'] ?? null,
                'duration_ms' => round($duration, 2),
                'outcome' => 'error',
                'error' => $e->getMessage(),
                'user_id' => 'local-default'
            ]);
            
            throw $e;
        }
    }
    
    private function extractGeneratedKeys($result, string $stepName): array
    {
        $keys = [];
        
        // Handle different result types based on step
        switch ($stepName) {
            case 'ExtractJsonMetadata':
                if (is_array($result)) {
                    $keys['metadata_fields'] = array_keys($result);
                    $keys['field_count'] = count($result);
                }
                break;
                
            case 'SuggestTags':
                if (is_array($result)) {
                    $keys['tag_count'] = count($result);
                    $keys['tag_ids'] = array_column($result, 'id');
                }
                break;
                
            case 'GenerateAutoTitle':
                if (is_string($result)) {
                    $keys['title_length'] = strlen($result);
                    $keys['title_generated'] = !empty($result);
                }
                break;
                
            case 'ParseAtomicFragment':
                if (is_object($result) && property_exists($result, 'id')) {
                    $keys['fragment_id'] = $result->id;
                    $keys['fragment_type'] = $result->type ?? null;
                }
                break;
        }
        
        return array_filter($keys);
    }
    
    private function getErrorCode(\Exception $e): string
    {
        // Map common exceptions to error codes
        $className = class_basename($e);
        
        return match($className) {
            'TimeoutException' => 'TIMEOUT',
            'ConnectionException' => 'CONNECTION_ERROR', 
            'ValidationException' => 'VALIDATION_ERROR',
            'UnauthorizedException' => 'AUTH_ERROR',
            default => 'UNKNOWN_ERROR'
        };
    }
}
```

### 1.2 Create Processing Telemetry Trait
**File**: `app/Services/Telemetry/HasProcessingTelemetry.php`

```php
<?php

namespace App\Services\Telemetry;

trait HasProcessingTelemetry
{
    protected function withTelemetry(string $stepName, callable $step, array $context = []): mixed
    {
        return app(ProcessingTelemetryDecorator::class)->wrapStep($stepName, $step, $context);
    }
    
    protected function getStepName(): string
    {
        return class_basename($this);
    }
    
    protected function getTelemetryContext(): array
    {
        return [
            'fragment_id' => $this->fragment->id ?? null,
            'step' => $this->getStepName()
        ];
    }
}
```

## Phase 2: ProcessFragmentJob Instrumentation (2 hours)

### 2.1 Update ProcessFragmentJob with Telemetry
**File**: `app/Jobs/ProcessFragmentJob.php`

```php
// Add to imports
use App\Services\Telemetry\ProcessingTelemetryDecorator;
use App\Services\Telemetry\HasCorrelationContext; // From TELEMETRY-001

class ProcessFragmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasCorrelationContext;
    
    public function handle()
    {
        $this->setupCorrelationLogging(); // From TELEMETRY-001
        
        $telemetry = app(ProcessingTelemetryDecorator::class);
        
        return $telemetry->wrapJob('ProcessFragmentJob', function ($context) {
            $jobId = $context['job_id'];
            $fragmentContext = [
                'fragment_id' => $this->fragment->id,
                'job_id' => $jobId
            ];
            
            $messages = [];
            $fragments = [];
            
            if (app()->runningUnitTests()) {
                return $this->handleTestMode($fragmentContext);
            }
            
            DB::beginTransaction();
            
            try {
                // Wrap each processing step
                $telemetry = app(ProcessingTelemetryDecorator::class);
                
                $telemetry->wrapStep('ParseAtomicFragment', function () use ($fragmentContext) {
                    return app(ParseAtomicFragment::class)($this->fragment);
                }, $fragmentContext);
                
                $telemetry->wrapStep('ExtractMetadataEntities', function () use ($fragmentContext) {
                    return app(ExtractMetadataEntities::class)($this->fragment);
                }, $fragmentContext);
                
                $telemetry->wrapStep('GenerateAutoTitle', function () use ($fragmentContext) {
                    return app(GenerateAutoTitle::class)($this->fragment);
                }, $fragmentContext);
                
                // ... existing pipeline logic
                
                DB::commit();
                
                $this->fragment->refresh();
                FragmentProcessed::dispatch($this->fragment);
                
                return [
                    'messages' => $messages,
                    'fragments' => [$this->fragment->toArray()],
                ];
                
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
            
        }, [
            'fragment_id' => $this->fragment->id,
            'processing_type' => 'fragment_enrichment'
        ]);
    }
    
    private function handleTestMode(array $context): array
    {
        $telemetry = app(ProcessingTelemetryDecorator::class);
        
        $telemetry->wrapStep('ParseAtomicFragment', function () {
            return app(ParseAtomicFragment::class)($this->fragment);
        }, $context);
        
        $telemetry->wrapStep('ExtractMetadataEntities', function () {
            return app(ExtractMetadataEntities::class)($this->fragment);
        }, $context);
        
        $telemetry->wrapStep('GenerateAutoTitle', function () {
            return app(GenerateAutoTitle::class)($this->fragment);
        }, $context);
        
        $this->fragment->refresh();
        
        return [
            'messages' => [],
            'fragments' => [$this->fragment->toArray()],
        ];
    }
}
```

## Phase 3: Action Instrumentation (3 hours)

### 3.1 Update ExtractJsonMetadata with Telemetry
**File**: `app/Actions/ExtractJsonMetadata.php`

```php
use App\Services\Telemetry\HasProcessingTelemetry;

class ExtractJsonMetadata
{
    use HasProcessingTelemetry;
    
    public function __invoke(Fragment $fragment): array
    {
        return $this->withTelemetry('ExtractJsonMetadata', function () use ($fragment) {
            // Existing extraction logic...
            return $extractedMetadata;
        }, ['fragment_id' => $fragment->id]);
    }
}
```

### 3.2 Update EnrichAssistantMetadata with Telemetry
**File**: `app/Actions/EnrichAssistantMetadata.php`

```php
use App\Services\Telemetry\HasProcessingTelemetry;

class EnrichAssistantMetadata
{
    use HasProcessingTelemetry;
    
    public function __invoke(Fragment $fragment): void
    {
        $this->withTelemetry('EnrichAssistantMetadata', function () use ($fragment) {
            // Existing enrichment logic...
            return $fragment; // Return fragment for key extraction
        }, ['fragment_id' => $fragment->id]);
    }
}
```

### 3.3 Update SuggestTags with Telemetry
**File**: `app/Actions/SuggestTags.php`

```php
use App\Services\Telemetry\HasProcessingTelemetry;

class SuggestTags
{
    use HasProcessingTelemetry;
    
    public function __invoke(Fragment $fragment): array
    {
        return $this->withTelemetry('SuggestTags', function () use ($fragment) {
            // Existing tag suggestion logic...
            return $suggestedTags;
        }, ['fragment_id' => $fragment->id]);
    }
}
```

### 3.4 Update ParseAtomicFragment with Telemetry
**File**: `app/Actions/ParseAtomicFragment.php`

```php
use App\Services\Telemetry\HasProcessingTelemetry;

class ParseAtomicFragment
{
    use HasProcessingTelemetry;
    
    public function __invoke(Fragment $fragment): Fragment
    {
        return $this->withTelemetry('ParseAtomicFragment', function () use ($fragment) {
            // Existing parsing logic...
            return $fragment;
        }, ['fragment_id' => $fragment->id]);
    }
}
```

### 3.5 Update GenerateAutoTitle with Telemetry
**File**: `app/Actions/GenerateAutoTitle.php`

```php
use App\Services\Telemetry\HasProcessingTelemetry;

class GenerateAutoTitle
{
    use HasProcessingTelemetry;
    
    public function __invoke(Fragment $fragment): ?string
    {
        return $this->withTelemetry('GenerateAutoTitle', function () use ($fragment) {
            // Existing title generation logic...
            return $generatedTitle;
        }, ['fragment_id' => $fragment->id]);
    }
}
```

## Phase 4: Additional Processing Points (1 hour)

### 4.1 Update ProcessAssistantFragment with Telemetry
**File**: `app/Actions/ProcessAssistantFragment.php`

```php
use App\Services\Telemetry\HasProcessingTelemetry;

class ProcessAssistantFragment
{
    use HasProcessingTelemetry;
    
    public function __invoke(Fragment $fragment): void
    {
        $this->withTelemetry('ProcessAssistantFragment', function () use ($fragment) {
            // Existing assistant processing logic...
            return $fragment;
        }, ['fragment_id' => $fragment->id]);
    }
}
```

### 4.2 Update Fragment Controller Enrichment Trigger
**File**: `app/Http/Controllers/FragmentController.php`

```php
// Around line 47 where enrichment is triggered
use App\Services\Telemetry\ProcessingTelemetryDecorator;

public function enrich(Fragment $fragment)
{
    $telemetry = app(ProcessingTelemetryDecorator::class);
    
    return $telemetry->wrapStep('FragmentEnrichmentTrigger', function () use ($fragment) {
        \Illuminate\Support\Facades\Log::debug('Starting enrichment pipeline', [
            'fragment_id' => $fragment->id
        ]);
        
        // Existing enrichment logic...
        
        \Illuminate\Support\Facades\Log::debug('Enrichment pipeline completed', [
            'fragment_id' => $fragment->id
        ]);
        
        return $freshFragment;
    }, ['fragment_id' => $fragment->id]);
}
```

## Phase 5: Testing & Validation (1 hour)

### 5.1 Unit Tests for ProcessingTelemetryDecorator
**File**: `tests/Unit/Services/Telemetry/ProcessingTelemetryDecoratorTest.php`

```php
<?php

namespace Tests\Unit\Services\Telemetry;

use App\Services\Telemetry\ProcessingTelemetryDecorator;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessingTelemetryDecoratorTest extends TestCase
{
    public function test_wrap_step_logs_success()
    {
        Log::spy();
        
        $decorator = new ProcessingTelemetryDecorator();
        
        $result = $decorator->wrapStep('TestStep', function () {
            return ['test' => 'result'];
        }, ['fragment_id' => 123]);
        
        $this->assertEquals(['test' => 'result'], $result);
        
        Log::shouldHaveReceived('info')
            ->with('processing.step.started', \Mockery::on(function ($context) {
                return $context['step'] === 'TestStep' &&
                       $context['fragment_id'] === 123;
            }));
            
        Log::shouldHaveReceived('info')
            ->with('processing.step.completed', \Mockery::on(function ($context) {
                return $context['step'] === 'TestStep' &&
                       $context['outcome'] === 'success' &&
                       isset($context['duration_ms']);
            }));
    }
    
    public function test_wrap_step_logs_failure()
    {
        Log::spy();
        
        $decorator = new ProcessingTelemetryDecorator();
        
        $this->expectException(\Exception::class);
        
        $decorator->wrapStep('TestStep', function () {
            throw new \Exception('Test error');
        }, ['fragment_id' => 123]);
        
        Log::shouldHaveReceived('error')
            ->with('processing.step.failed', \Mockery::on(function ($context) {
                return $context['step'] === 'TestStep' &&
                       $context['outcome'] === 'error' &&
                       $context['error'] === 'Test error';
            }));
    }
}
```

### 5.2 Integration Tests for Fragment Processing
**File**: `tests/Feature/Telemetry/FragmentProcessingTelemetryTest.php`

```php
<?php

namespace Tests\Feature\Telemetry;

use App\Models\Fragment;
use App\Jobs\ProcessFragmentJob;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FragmentProcessingTelemetryTest extends TestCase
{
    public function test_process_fragment_job_telemetry()
    {
        Log::spy();
        
        $fragment = Fragment::factory()->create();
        
        $job = new ProcessFragmentJob($fragment);
        $job->handle();
        
        // Verify job-level telemetry
        Log::shouldHaveReceived('info')
            ->with('processing.job.started', \Mockery::on(function ($context) use ($fragment) {
                return $context['job'] === 'ProcessFragmentJob' &&
                       $context['fragment_id'] === $fragment->id;
            }));
            
        // Verify step-level telemetry
        Log::shouldHaveReceived('info')
            ->with('processing.step.completed', \Mockery::on(function ($context) {
                return in_array($context['step'], [
                    'ParseAtomicFragment',
                    'ExtractMetadataEntities', 
                    'GenerateAutoTitle'
                ]);
            }));
    }
}
```

## Implementation Checklist

### Core Infrastructure
- [ ] Create `ProcessingTelemetryDecorator` service
- [ ] Create `HasProcessingTelemetry` trait  
- [ ] Implement key extraction logic for different step types
- [ ] Add error code mapping for common exceptions

### Job Instrumentation
- [ ] Update `ProcessFragmentJob` with job-level telemetry
- [ ] Wrap individual processing steps in telemetry
- [ ] Handle test mode instrumentation
- [ ] Integrate with correlation context from TELEMETRY-001

### Action Instrumentation  
- [ ] Update `ExtractJsonMetadata` with telemetry
- [ ] Update `EnrichAssistantMetadata` with telemetry
- [ ] Update `SuggestTags` with telemetry
- [ ] Update `ParseAtomicFragment` with telemetry
- [ ] Update `GenerateAutoTitle` with telemetry
- [ ] Update `ProcessAssistantFragment` with telemetry

### Controller Integration
- [ ] Update `FragmentController` enrichment trigger
- [ ] Ensure proper context propagation

### Testing
- [ ] Unit tests for telemetry decorator
- [ ] Integration tests for fragment processing pipeline
- [ ] Performance tests for telemetry overhead
- [ ] Error handling tests

## Success Metrics

- All fragment processing steps instrumented with telemetry
- Step timing captured with <1ms overhead per step
- Generated object keys logged without content
- Error context preserved for debugging
- Integration with correlation IDs from TELEMETRY-001
- Job-level and step-level telemetry working together