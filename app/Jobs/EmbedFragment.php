<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            Log::debug('EmbedFragment: embeddings disabled, skipping', ['fragment_id' => $this->fragmentId]);

            return;
        }

        $fragment = \App\Models\Fragment::find($this->fragmentId);
        if (! $fragment) {
            Log::warning('EmbedFragment: fragment not found', ['fragment_id' => $this->fragmentId]);

            return;
        }

        $text = trim($fragment->edited_message ?? $fragment->message ?? '');
        if ($text === '') {
            Log::debug('EmbedFragment: empty text, skipping', ['fragment_id' => $this->fragmentId]);

            return;
        }

        // Check if we have pgvector support before attempting to embed
        if (! $this->hasPgVectorSupport()) {
            Log::warning('EmbedFragment: pgvector extension not available, skipping', [
                'fragment_id' => $this->fragmentId,
                'database' => DB::connection()->getDriverName(),
            ]);

            return;
        }

        try {
            // Build context for operation-specific embedding selection
            $context = [
                'operation_type' => 'embedding',
                'command' => 'embed_text',
                'fragment_id' => $fragment->id,
                'vault' => $fragment->vault,
                'project_id' => $fragment->project_id,
            ];

            $res = $emb->embed($text, $context); // returns ['dims'=>..,'vector'=>[..],'provider'=>..,'model'=>..]
            $vec = '['.implode(',', $res['vector']).']';

            DB::statement('
                INSERT INTO fragment_embeddings (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?::vector, ?, now(), now())
                ON CONFLICT (fragment_id, provider, model, content_hash)
                DO UPDATE SET dims = EXCLUDED.dims, embedding = EXCLUDED.embedding, updated_at = now()
            ', [$fragment->id, $this->provider, $this->model, $res['dims'], $vec, $this->contentHash]);

            Log::debug('EmbedFragment: embedding saved', [
                'fragment_id' => $this->fragmentId,
                'provider' => $this->provider,
                'model' => $this->model,
                'dimensions' => $res['dims'],
            ]);
        } catch (\Throwable $e) {
            Log::error('EmbedFragment: failed to create embedding', [
                'fragment_id' => $this->fragmentId,
                'provider' => $this->provider,
                'model' => $this->model,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw for job retry mechanisms
        }
    }

    private function hasPgVectorSupport(): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            return false;
        }

        try {
            // Check if pgvector extension is installed
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");

            return ! empty($result);
        } catch (\Throwable $e) {
            Log::warning('EmbedFragment: could not check pgvector availability', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
