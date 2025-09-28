<?php

namespace App\Actions;

use App\Models\Fragment;
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

        Log::debug('Assistant fragment created', [
            'fragment_id' => $assistantFragment->id,
            'conversation_id' => $assistantData['conversation_id'],
        ]);

        // Run enrichment pipeline (async in production, sync in testing)
        $enrichmentJob = function () use ($assistantFragment, $assistantData) {
            try {
                // Reload fragment from database to ensure fresh state
                $freshFragment = Fragment::find($assistantFragment->id);
                if (! $freshFragment) {
                    Log::error('Assistant fragment not found for enrichment', ['fragment_id' => $assistantFragment->id]);

                    return;
                }

                Log::debug('Starting assistant enrichment pipeline', ['fragment_id' => $freshFragment->id]);

                app(\Illuminate\Pipeline\Pipeline::class)
                    ->send(['fragment' => $freshFragment, 'data' => $assistantData])
                    ->through([
                        \App\Actions\ExtractJsonMetadata::class,
                        \App\Actions\EnrichAssistantMetadata::class,
                        \App\Actions\DriftSync::class,
                        \App\Actions\InferFragmentType::class,
                        \App\Actions\SuggestTags::class,
                    ])
                    ->thenReturn();

                Log::debug('Assistant enrichment pipeline completed', ['fragment_id' => $freshFragment->id]);
            } catch (\Throwable $e) {
                Log::error('Assistant enrichment pipeline failed', [
                    'fragment_id' => $assistantFragment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

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
