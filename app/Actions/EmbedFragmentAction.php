<?php

namespace App\Actions;

use App\Jobs\EmbedFragment;
use App\Models\Fragment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // make sure this exists

class EmbedFragmentAction
{
    public function handle(Fragment $fragment, $next)
    {
        $fragment = $this->__invoke($fragment);

        return $next($fragment);
    }

    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('EmbedFragmentAction::__invoke()', ['fragment_id' => $fragment->id]);

        if (! config('fragments.embeddings.enabled')) {
            Log::debug('EmbedFragmentAction: embeddings disabled, skipping');

            return $fragment;
        }

        // Choose text to embed (prefer edited)
        $text = trim($fragment->edited_message ?? $fragment->message ?? '');
        if ($text === '') {
            Log::debug('EmbedFragmentAction: empty text, skipping');

            return $fragment;
        }

        // Check if embedding operation is enabled
        if (! config('fragments.models.operations.embedding.enabled', true)) {
            Log::debug('EmbedFragmentAction: embedding operation disabled, skipping');

            return $fragment;
        }

        // Get provider/model from operation-specific config or fall back to embedding config
        $operationProvider = config('fragments.models.operations.embedding.provider');
        $operationModel = config('fragments.models.operations.embedding.model');

        $provider = $operationProvider ?: config('fragments.embeddings.provider');
        $model = $operationModel ?: config('fragments.embeddings.model');
        $version = (string) config('fragments.embeddings.version', '1');
        $contentHash = hash('sha256', $text.'|'.$provider.'|'.$model.'|'.$version);

        // Idempotence: if we already have the same hash for this provider+model, skip
        $exists = false;
        try {
            $exists = DB::table('fragment_embeddings')
                ->where('fragment_id', $fragment->id)
                ->where('provider', $provider)
                ->where('model', $model)
                ->where('content_hash', $contentHash)
                ->exists();
        } catch (\Throwable $e) {
            // If model/content_hash columns don't exist yet, fall back to a looser check
            $exists = DB::table('fragment_embeddings')
                ->where('fragment_id', $fragment->id)
                ->where('provider', $provider)
                ->exists();
        }

        if ($exists) {
            Log::debug('EmbedFragmentAction: up-to-date embedding exists', compact('provider', 'model'));

            return $fragment;
        }

        // Enqueue async embed; job writes/updates fragment_embeddings
        dispatch(new EmbedFragment(
            fragmentId: $fragment->id,
            provider: $provider,
            model: $model,
            contentHash: $contentHash
        ))->onQueue('embeddings');

        Log::debug('EmbedFragmentAction: queued', compact('provider', 'model'));

        return $fragment;
    }
}
