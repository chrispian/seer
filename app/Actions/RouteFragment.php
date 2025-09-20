<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class RouteFragment
{
    public function __invoke(string $input): Fragment
    {
        Log::debug('RouteFragment::invoke()');

        // Normalize input to check for duplicates
        $normalization = app(NormalizeInput::class)($input);

        // Check for existing fragment within the time bucket to prevent duplicates
        $existingFragment = Fragment::where('input_hash', $normalization['hash'])
            ->where('hash_bucket', $normalization['bucket'])
            ->first();

        if ($existingFragment) {
            Log::debug('Duplicate fragment detected', [
                'existing_id' => $existingFragment->id,
                'hash' => $normalization['hash'],
                'bucket' => $normalization['bucket'],
            ]);

            return $existingFragment;
        }

        // Get default vault and project
        $defaultVault = \App\Models\Vault::getDefault();
        $defaultProject = \App\Models\Project::getDefaultForVault($defaultVault->id);

        // Create new fragment with normalized data
        $fragment = Fragment::create([
            'vault' => $defaultVault->name ?? 'default',
            'project_id' => $defaultProject->id ?? null,
            'type' => 'log',
            'message' => $input, // Keep original message for display
            'source' => 'chat',
            'input_hash' => $normalization['hash'],
            'hash_bucket' => $normalization['bucket'],
        ]);

        Log::debug('New fragment created', [
            'id' => $fragment->id,
            'hash' => $normalization['hash'],
            'bucket' => $normalization['bucket'],
        ]);

        // Dispatch enrichment pipeline (async) - same as ParseChaosFragment
        dispatch(function () use ($fragment) {
            try {
                // Reload fragment from database to ensure fresh state
                $freshFragment = Fragment::find($fragment->id);
                if (! $freshFragment) {
                    Log::error('Fragment not found for enrichment', ['fragment_id' => $fragment->id]);

                    return;
                }

                Log::debug('Starting enrichment pipeline', ['fragment_id' => $freshFragment->id]);

                app(\Illuminate\Pipeline\Pipeline::class)
                    ->send($freshFragment)
                    ->through([
                        \App\Actions\DriftSync::class,
                        \App\Actions\ParseAtomicFragment::class,
                        \App\Actions\EnrichFragmentWithLlama::class,
                        \App\Actions\InferFragmentType::class,
                        \App\Actions\SuggestTags::class,
                        \App\Actions\RouteToVault::class,
                    ])
                    ->thenReturn();

                Log::debug('Enrichment pipeline completed', ['fragment_id' => $freshFragment->id]);
            } catch (\Throwable $e) {
                Log::error('Enrichment pipeline failed', [
                    'fragment_id' => $fragment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $fragment->refresh();
                $fragment->metadata = array_merge($fragment->metadata ?? [], [
                    'enrichment_status' => 'pipeline_failed',
                    'error' => $e->getMessage(),
                ]);
                $fragment->save();
            }
        })->onQueue('fragments');

        return $fragment;
    }
}
