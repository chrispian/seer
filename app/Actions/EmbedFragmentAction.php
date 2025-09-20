<?php

namespace App\Actions;

use App\Jobs\EmbedFragment;
use App\Models\Fragment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // make sure this exists

class EmbedFragmentAction
{
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

        // Provider/model from env (keep simple + overridable)
        $provider = config('fragments.embeddings.provider');   // e.g. 'openai' or 'ollama'
        $model = config('fragments.embeddings.model');      // e.g. 'text-embedding-3-small'
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
