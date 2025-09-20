<?php

namespace App\Jobs;

// app/Jobs/EmbedFragment.php
class EmbedFragment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $fragmentId,
        public string $provider,
        public string $model,
        public string $contentHash
    ) {}

    public function handle(\App\Services\AI\Embeddings $emb): void
    {
        if (! config('fragments.embeddings.enabled')) {
            return;
        }

        $fragment = \App\Models\Fragment::find($this->fragmentId);
        if (! $fragment) {
            return;
        }

        $text = trim($fragment->edited_message ?? $fragment->message ?? '');
        if ($text === '') {
            return;
        }

        $res = $emb->embed($text, $this->provider); // returns ['dims'=>..,'vector'=>[..],'provider'=>..,'model'=>..]
        $vec = '['.implode(',', $res['vector']).']';

        \DB::statement('
            INSERT INTO fragment_embeddings (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?::vector, ?, now(), now())
            ON CONFLICT (fragment_id, provider, model, content_hash)
            DO UPDATE SET dims = EXCLUDED.dims, embedding = EXCLUDED.embedding, updated_at = now()
        ', [$fragment->id, $this->provider, $this->model, $res['dims'], $vec, $this->contentHash]);
    }
}
