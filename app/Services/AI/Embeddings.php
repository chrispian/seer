<?php

namespace App\Services\AI;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class Embeddings
{
    protected ModelSelectionService $modelSelection;

    public function __construct(ModelSelectionService $modelSelection)
    {
        $this->modelSelection = $modelSelection;
    }

    public function embed(string $text, array $context = []): array
    {
        // Normalize whitespace & length (keep under ~8k tokens)
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Select appropriate embedding model
        $selectedModel = $this->modelSelection->selectEmbeddingModel($context);
        $provider = $selectedModel['provider'];
        $model = $selectedModel['model'];

        if ($provider === 'openai') {
            return $this->embedWithOpenAI($text, $model);
        }

        if ($provider === 'ollama') {
            return $this->embedWithOllama($text, $model);
        }

        throw new \RuntimeException("Unknown embeddings provider: $provider");
    }

    protected function embedWithOpenAI(string $text, string $model): array
    {
        $apiKey = config('services.openai.key');

        $resp = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $model,
                'input' => $text,
            ])->throw()->json();

        $vec = Arr::get($resp, 'data.0.embedding', []);

        return ['dims' => count($vec), 'vector' => $vec, 'provider' => 'openai', 'model' => $model];
    }

    protected function embedWithOllama(string $text, string $model): array
    {
        $base = rtrim(config('services.ollama.base', 'http://127.0.0.1:11434'), '/');

        $resp = Http::post("$base/api/embeddings", [
            'model' => $model,
            'prompt' => $text,
        ])->throw()->json();

        $vec = Arr::get($resp, 'embedding', []);

        return ['dims' => count($vec), 'vector' => $vec, 'provider' => 'ollama', 'model' => $model];
    }

    /**
     * Legacy method for backward compatibility
     */
    public function embedLegacy(string $text, string $provider = 'openai'): array
    {
        return $this->embed($text, ['command_model_override' => $provider]);
    }
}
