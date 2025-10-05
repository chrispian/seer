<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Services\Telemetry\ChatTelemetry;
use Illuminate\Support\Facades\Log;

class ProcessAssistantFragment
{
    public function __invoke(array $assistantData): Fragment
    {
        Log::debug('ProcessAssistantFragment::invoke()');

        // Create the assistant fragment using RouteFragment
        $routeFragment = app(RouteFragment::class);
        $assistantFragment = $routeFragment($assistantData['message']);

        // Update with assistant-specific metadata and source
        $assistantFragment->update([
            'source' => 'chat-ai',
            'model_provider' => $assistantData['provider'],
            'model_name' => $assistantData['model'],
            'relationships' => [
                'in_reply_to_id' => $assistantData['user_fragment_id'],
                'conversation_id' => $assistantData['conversation_id'],
            ],
        ]);

        $fragmentCreationTime = microtime(true);

        ChatTelemetry::logAssistantFragmentCreated($assistantFragment->id, [
            'conversation_id' => $assistantData['conversation_id'],
            'in_reply_to_id' => $assistantData['user_fragment_id'],
            'provider' => $assistantData['provider'],
            'model' => $assistantData['model'],
            'fragment_creation_time_ms' => round(($fragmentCreationTime - ($assistantData['start_time'] ?? $fragmentCreationTime)) * 1000, 2),
        ]);

        // Run enrichment pipeline (async in production, sync in testing)
        $enrichmentJob = function () use ($assistantFragment, $assistantData) {
            $enrichmentStartTime = microtime(true);

            try {
                // Reload fragment from database to ensure fresh state
                $freshFragment = Fragment::find($assistantFragment->id);
                if (! $freshFragment) {
                    Log::error('Assistant fragment not found for enrichment', ['fragment_id' => $assistantFragment->id]);

                    return;
                }

                $pipelineSteps = [
                    \App\Actions\ExtractJsonMetadata::class,
                    \App\Actions\EnrichAssistantMetadata::class,
                    \App\Actions\DriftSync::class,
                    \App\Actions\InferFragmentType::class,
                    \App\Actions\SuggestTags::class,
                ];

                ChatTelemetry::logEnrichmentPipelineStarted($freshFragment->id, $pipelineSteps);

                // Execute pipeline with per-step telemetry
                $pipelineData = ['fragment' => $freshFragment, 'data' => $assistantData];

                foreach ($pipelineSteps as $step) {
                    $stepStart = microtime(true);
                    try {
                        $pipelineData = app($step)->handle($pipelineData, function ($passable) {
                            return $passable;
                        });

                        $stepDuration = (microtime(true) - $stepStart) * 1000;
                        ChatTelemetry::logEnrichmentStep($freshFragment->id, [
                            'step' => class_basename($step),
                            'duration_ms' => round($stepDuration, 2),
                            'success' => true,
                        ]);
                    } catch (\Throwable $e) {
                        $stepDuration = (microtime(true) - $stepStart) * 1000;
                        ChatTelemetry::logEnrichmentStep($freshFragment->id, [
                            'step' => class_basename($step),
                            'duration_ms' => round($stepDuration, 2),
                            'success' => false,
                            'error' => $e->getMessage(),
                            'error_class' => get_class($e),
                        ]);
                        throw $e;
                    }
                }

                $enrichmentDuration = (microtime(true) - $enrichmentStartTime) * 1000;
                ChatTelemetry::logEnrichmentPipelineCompleted($freshFragment->id, $enrichmentDuration);

                // Log fragment correlation for conversation tracking
                $totalProcessingTime = round((microtime(true) - $fragmentCreationTime) * 1000, 2);
                ChatTelemetry::logFragmentCorrelation([
                    'user_fragment_id' => $assistantData['user_fragment_id'],
                    'assistant_fragment_id' => $assistantFragment->id,
                    'conversation_id' => $assistantData['conversation_id'],
                    'processing_chain' => [
                        'fragment_creation_ms' => round(($enrichmentStartTime - $fragmentCreationTime) * 1000, 2),
                        'enrichment_duration_ms' => round($enrichmentDuration, 2),
                        'total_processing_ms' => $totalProcessingTime,
                    ],
                    'total_conversation_time_ms' => $totalProcessingTime,
                ]);

            } catch (\Throwable $e) {
                $enrichmentDuration = (microtime(true) - $enrichmentStartTime) * 1000;
                ChatTelemetry::logEnrichmentPipelineError($assistantFragment->id, $e, $enrichmentDuration);

                $assistantFragment->refresh();
                $assistantFragment->metadata = array_merge($assistantFragment->metadata ?? [], [
                    'enrichment_status' => 'pipeline_failed',
                    'error' => $e->getMessage(),
                ]);
                $assistantFragment->save();
            }
        };

        if (app()->environment('testing')) {
            // Run synchronously in tests
            $enrichmentJob();
        } else {
            // Queue in production
            dispatch($enrichmentJob)->onQueue('assistant-processing');
        }

        return $assistantFragment;
    }
}
