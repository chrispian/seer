<?php

namespace App\Jobs;

use App\Contracts\EmbeddingStoreInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    public function handle(\App\Services\AI\Embeddings $emb, EmbeddingStoreInterface $store): void
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

        // Check if vector support is available before attempting to embed
        if (! $store->isVectorSupportAvailable()) {
            $driverInfo = $store->getDriverInfo();
            Log::warning('EmbedFragment: vector extension not available, skipping', [
                'fragment_id' => $this->fragmentId,
                'driver' => $driverInfo['driver'],
                'extension' => $driverInfo['extension'],
                'available' => $driverInfo['available'],
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

            // Use abstraction layer for storage
            $store->store(
                fragmentId: $fragment->id,
                provider: $this->provider,
                model: $this->model,
                dimensions: $res['dims'],
                vector: $res['vector'],
                contentHash: $this->contentHash
            );

            Log::debug('EmbedFragment: embedding saved via abstraction layer', [
                'fragment_id' => $this->fragmentId,
                'provider' => $this->provider,
                'model' => $this->model,
                'dimensions' => $res['dims'],
                'driver' => $store->getDriverInfo()['driver'],
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


}
