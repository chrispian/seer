<?php

namespace App\Jobs;

use App\Decorators\TelemetryPipelineDecorator;
use App\Events\FragmentProcessed;
use App\Models\Fragment;
use App\Services\Telemetry\FragmentProcessingTelemetry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFragmentJobWithTelemetry implements ShouldQueue
{
    use Dispatchable, HasCorrelationContext, InteractsWithQueue, Queueable, SerializesModels;

    public Fragment $fragment;

    public function __construct(Fragment $fragment)
    {
        $this->fragment = $fragment;
    }

    public function handle()
    {
        // Restore correlation context for this job
        $this->restoreCorrelationContext();

        $messages = [];
        $fragments = [];
        $pipelineId = null;
        $stepMetrics = [];

        $startTime = microtime(true);

        if (app()->runningUnitTests()) {
            return $this->handleTestMode();
        }

        DB::beginTransaction();

        try {
            // Define the pipeline steps
            $steps = [
                \App\Actions\DriftSync::class,
                \App\Actions\ParseAtomicFragment::class,
                \App\Actions\ExtractMetadataEntities::class,
                \App\Actions\GenerateAutoTitle::class,
                \App\Actions\EnrichFragmentWithAI::class,
                \App\Actions\InferFragmentType::class,
                \App\Actions\SuggestTags::class,
                \App\Actions\RouteToVault::class,
                \App\Actions\EmbedFragmentAction::class,
            ];

            // Initialize pipeline telemetry
            $pipelineId = FragmentProcessingTelemetry::logPipelineStarted($this->fragment, $steps);

            Log::info('🔧 Processing Fragment with Telemetry', array_merge(
                $this->getJobContext(),
                [
                    'pipeline_id' => $pipelineId,
                    'fragment_id' => $this->fragment->id,
                    'fragment_type' => $this->fragment->type?->value,
                    'processing_stage' => 'start',
                ]
            ));

            // Wrap each step with telemetry decorator
            $decoratedSteps = [];
            foreach ($steps as $stepClass) {
                $stepInstance = app($stepClass);
                $stepName = class_basename($stepClass);
                
                $decoratedSteps[] = new TelemetryPipelineStepDecorator(
                    $stepInstance,
                    $stepName,
                    $pipelineId,
                    function($stepName, $durationMs, $success, $error = null, $context = []) use (&$stepMetrics) {
                        $stepMetrics[] = [
                            'step_name' => $stepName,
                            'duration_ms' => $durationMs,
                            'success' => $success,
                            'error' => $error,
                            'context' => $context,
                        ];
                    }
                );
            }

            // Execute the decorated pipeline
            $processed = app(Pipeline::class)
                ->send($this->fragment)
                ->through($decoratedSteps)
                ->thenReturn();

            $messages[] = "📦 Fragment stored: `{$this->fragment->message}`";

            $fragments[] = [
                'id' => $this->fragment->id,
                'type' => $this->fragment->type?->value ?? 'log',
                'message' => $this->fragment->message,
            ];

            DB::commit();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log successful pipeline completion
            FragmentProcessingTelemetry::logPipelineCompleted($pipelineId, $this->fragment, $processingTime, $stepMetrics);

            Log::info('✅ Fragment processing complete with telemetry', array_merge(
                $this->getJobContext(),
                [
                    'pipeline_id' => $pipelineId,
                    'fragment_id' => $this->fragment->id,
                    'processing_time_ms' => $processingTime,
                    'processing_stage' => 'complete',
                    'fragments_created' => count($fragments),
                    'step_count' => count($stepMetrics),
                    'successful_steps' => count(array_filter($stepMetrics, fn($s) => $s['success'])),
                ]
            ));

        } catch (\Throwable $e) {
            DB::rollBack();

            $messages[] = "⚠️ Failed to store fragment: {$e->getMessage()}";

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log pipeline failure
            if ($pipelineId) {
                FragmentProcessingTelemetry::logPipelineFailed($pipelineId, $this->fragment, $e, $processingTime, $stepMetrics);
            }

            Log::error('❌ Fragment processing failed with telemetry', array_merge(
                $this->getJobContext(),
                [
                    'pipeline_id' => $pipelineId,
                    'fragment_id' => $this->fragment->id,
                    'error' => $e->getMessage(),
                    'processing_time_ms' => $processingTime,
                    'processing_stage' => 'failed',
                    'exception_class' => get_class($e),
                    'completed_steps' => count($stepMetrics),
                    'failed_steps' => count(array_filter($stepMetrics, fn($s) => !$s['success'])),
                ]
            ));
        }

        FragmentProcessed::dispatch(
            $this->fragment->id,
            count($fragments),
            $fragments
        );

        return [
            'messages' => $messages,
            'fragments' => $fragments,
            'telemetry' => [
                'pipeline_id' => $pipelineId,
                'step_metrics' => $stepMetrics,
                'processing_time_ms' => $processingTime ?? 0,
            ],
        ];
    }

    private function handleTestMode(): array
    {
        // Simple execution for tests without telemetry overhead
        app(\App\Actions\ParseAtomicFragment::class)($this->fragment);
        app(\App\Actions\ExtractMetadataEntities::class)($this->fragment);
        app(\App\Actions\GenerateAutoTitle::class)($this->fragment);

        $this->fragment->refresh();

        return [
            'messages' => [],
            'fragments' => [$this->fragment->toArray()],
        ];
    }
}

/**
 * Pipeline step decorator that captures telemetry for each step
 */
class TelemetryPipelineStepDecorator
{
    protected $action;
    protected string $stepName;
    protected string $pipelineId;
    protected $metricsCallback;

    public function __construct($action, string $stepName, string $pipelineId, callable $metricsCallback)
    {
        $this->action = $action;
        $this->stepName = $stepName;
        $this->pipelineId = $pipelineId;
        $this->metricsCallback = $metricsCallback;
    }

    public function handle(Fragment $fragment, $next)
    {
        $startTime = microtime(true);
        $beforeFragment = clone $fragment;
        $success = true;
        $error = null;
        $result = null;

        try {
            $result = $this->action->handle($fragment, fn($f) => $f);
            
            // Log state changes
            FragmentProcessingTelemetry::logFragmentStateChange($this->pipelineId, $this->stepName, $beforeFragment, $result);
            
        } catch (\Throwable $e) {
            $success = false;
            $error = $e;
            $result = $fragment; // Return original fragment on error
            
            // Don't re-throw here, let the pipeline handle it
            throw $e;
        } finally {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log step execution
            FragmentProcessingTelemetry::logStepExecution(
                $this->pipelineId,
                $this->stepName,
                $result ?? $fragment,
                $durationMs,
                $success,
                $error,
                [
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                ]
            );
            
            // Call metrics callback
            ($this->metricsCallback)($this->stepName, $durationMs, $success, $error, [
                'fragment_id' => ($result ?? $fragment)->id,
            ]);

            // Check for performance alerts
            if ($durationMs > 5000) { // 5 second threshold
                FragmentProcessingTelemetry::logPerformanceAlert($this->pipelineId, 'slow_step', [
                    'step_name' => $this->stepName,
                    'duration_ms' => $durationMs,
                    'threshold_ms' => 5000,
                ]);
            }
        }

        return $next($result);
    }
}