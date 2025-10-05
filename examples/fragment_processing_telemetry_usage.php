<?php

/*
|--------------------------------------------------------------------------
| Fragment Processing Telemetry Usage Examples
|--------------------------------------------------------------------------
|
| This file demonstrates how to use the TELEMETRY-003 Fragment Processing
| Telemetry Decorator system for enhanced observability in fragment
| processing pipelines.
|
*/

use App\Models\Fragment;
use App\Services\Telemetry\TelemetryPipelineBuilder;
use App\Services\Telemetry\FragmentProcessingTelemetry;
use App\Decorators\TelemetryPipelineDecorator;

// Example 1: Using TelemetryPipelineBuilder for complete pipeline processing
function processFragmentWithFullTelemetry(Fragment $fragment): Fragment
{
    return TelemetryPipelineBuilder::standard()
        ->withContext([
            'source' => 'api',
            'user_id' => auth()->id(),
            'priority' => 'normal',
        ])
        ->process($fragment);
}

// Example 2: Creating a custom pipeline with specific steps
function processFragmentWithCustomPipeline(Fragment $fragment): Fragment
{
    return TelemetryPipelineBuilder::create()
        ->addStep(\App\Actions\ParseAtomicFragment::class, 'parse', ['validate_syntax' => true])
        ->addStep(\App\Actions\ExtractMetadataEntities::class, 'extract_entities')
        ->addStep(\App\Actions\EnrichFragmentWithAI::class, 'ai_enrichment', ['model_preference' => 'fast'])
        ->withContext(['pipeline_type' => 'custom_enrichment'])
        ->process($fragment);
}

// Example 3: Using lightweight pipeline for simple processing
function processFragmentLightweight(Fragment $fragment): Fragment
{
    return TelemetryPipelineBuilder::lightweight()
        ->withContext(['processing_mode' => 'lightweight'])
        ->process($fragment);
}

// Example 4: Processing single action with telemetry
function enrichFragmentWithTelemetry(Fragment $fragment): Fragment
{
    return TelemetryPipelineBuilder::executeAction(
        \App\Actions\EnrichFragmentWithAI::class,
        $fragment,
        ['operation' => 'standalone_enrichment']
    );
}

// Example 5: Manual telemetry decorator usage
function manualTelemetryExample(Fragment $fragment): Fragment
{
    $parseAction = app(\App\Actions\ParseAtomicFragment::class);
    
    $decoratedAction = TelemetryPipelineDecorator::wrap(
        $parseAction,
        'custom_parse_step',
        ['manual_execution' => true]
    );
    
    return $decoratedAction($fragment);
}

// Example 6: Batch processing with correlation
function processBatchWithCorrelation(array $fragments): array
{
    $results = [];
    $batchId = \Illuminate\Support\Str::uuid();
    
    // Log batch correlation
    FragmentProcessingTelemetry::logFragmentCorrelation(
        array_column($fragments, 'id'),
        'batch_processing',
        ['batch_id' => $batchId, 'batch_size' => count($fragments)]
    );
    
    foreach ($fragments as $fragment) {
        $results[] = TelemetryPipelineBuilder::standard()
            ->withContext([
                'batch_id' => $batchId,
                'batch_position' => count($results) + 1,
                'batch_total' => count($fragments),
            ])
            ->process($fragment);
    }
    
    return $results;
}

// Example 7: Conditional telemetry based on environment
function processFragmentConditionalTelemetry(Fragment $fragment): Fragment
{
    $telemetryEnabled = config('fragment-telemetry.enabled') && !app()->runningUnitTests();
    
    return TelemetryPipelineBuilder::standard()
        ->withTelemetry($telemetryEnabled)
        ->withContext(['environment' => app()->environment()])
        ->process($fragment);
}

// Example 8: High-priority processing with performance monitoring
function processHighPriorityFragment(Fragment $fragment): Fragment
{
    $startTime = microtime(true);
    
    $result = TelemetryPipelineBuilder::create()
        ->addSteps([
            [\App\Actions\ParseAtomicFragment::class, 'priority_parse', ['priority' => 'high']],
            [\App\Actions\EnrichFragmentWithAI::class, 'priority_enrichment', ['timeout' => 30]],
            [\App\Actions\InferFragmentType::class, 'priority_inference'],
        ])
        ->withContext([
            'priority' => 'high',
            'sla_target_ms' => 5000,
        ])
        ->process($fragment);
    
    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
    
    // Alert if SLA exceeded
    if ($durationMs > 5000) {
        FragmentProcessingTelemetry::logPerformanceAlert(
            'manual-pipeline',
            'sla_exceeded',
            [
                'fragment_id' => $fragment->id,
                'duration_ms' => $durationMs,
                'sla_target_ms' => 5000,
                'priority' => 'high',
            ]
        );
    }
    
    return $result;
}

// Example 9: Error handling and recovery with telemetry
function processFragmentWithErrorRecovery(Fragment $fragment): Fragment
{
    try {
        return TelemetryPipelineBuilder::standard()
            ->withContext(['error_recovery_enabled' => true])
            ->process($fragment);
            
    } catch (\Exception $e) {
        // Log the error and attempt fallback processing
        FragmentProcessingTelemetry::logPerformanceAlert(
            'error-recovery-pipeline',
            'primary_pipeline_failed',
            [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
                'attempting_fallback' => true,
            ]
        );
        
        // Fallback to lightweight processing
        return TelemetryPipelineBuilder::lightweight()
            ->withContext([
                'fallback_mode' => true,
                'original_error' => $e->getMessage(),
            ])
            ->process($fragment);
    }
}

// Example 10: Integration with job queue
class ProcessFragmentWithTelemetryJob
{
    use \App\Jobs\HasCorrelationContext;
    
    public Fragment $fragment;
    public array $context;
    
    public function __construct(Fragment $fragment, array $context = [])
    {
        $this->fragment = $fragment;
        $this->context = $context;
    }
    
    public function handle(): Fragment
    {
        $this->restoreCorrelationContext();
        
        return TelemetryPipelineBuilder::standard()
            ->withContext(array_merge($this->context, [
                'job_execution' => true,
                'queue' => $this->queue ?? 'default',
                'correlation_id' => $this->correlationId,
            ]))
            ->process($this->fragment);
    }
}

/*
|--------------------------------------------------------------------------
| Configuration Examples
|--------------------------------------------------------------------------
*/

// Example environment configuration (.env)
/*
FRAGMENT_TELEMETRY_ENABLED=true
FRAGMENT_TELEMETRY_PIPELINE_CHANNEL=fragment-processing-telemetry
FRAGMENT_TELEMETRY_PIPELINE_SAMPLING=1.0
FRAGMENT_TELEMETRY_STEP_SAMPLING=1.0
FRAGMENT_TELEMETRY_DEBUG=false
FRAGMENT_TELEMETRY_METRICS_ENABLED=false
*/

// Example config override in specific environments
/*
// config/fragment-telemetry.php (production overrides)
if (app()->environment('production')) {
    return array_merge(require __DIR__.'/fragment-telemetry.php', [
        'sampling' => [
            'pipeline_events' => 0.1,  // Sample 10% in production
            'step_events' => 0.05,     // Sample 5% in production
            'state_changes' => 0.2,    // Sample 20% in production
        ],
        'performance' => [
            'alert_thresholds' => [
                'slow_step' => 2000,        // Stricter thresholds in prod
                'slow_pipeline' => 10000,
            ],
        ],
    ]);
}
*/