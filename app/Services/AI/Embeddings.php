<?php
namespace App\Services\AI;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class Embeddings
{
    public function embed(string $text, string $provider = 'openai'): array
    {
        // Normalize whitespace & length (keep under ~8k tokens)
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($provider === 'openai') {
            // via Prism if you’ve exposed embeddings, or call directly:
            $apiKey = config('services.openai.key');
            $model  = config('services.openai.embedding_model', 'text-embedding-3-small');

            $resp = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $model,
                    'input' => $text,
                ])->throw()->json();

            $vec = Arr::get($resp, 'data.0.embedding', []);
            return ['dims' => count($vec), 'vector' => $vec, 'provider' => 'openai', 'model' => $model];
        }

        if ($provider === 'ollama') {
            $base  = rtrim(config('services.ollama.base', 'http://127.0.0.1:11434'), '/');
            $model = config('services.ollama.embedding_model', 'nomic-embed-text');

            $resp = Http::post("$base/api/embeddings", [
                'model' => $model,
                'prompt'=> $text,
            ])->throw()->json();

            $vec = Arr::get($resp, 'embedding', []);
            return ['dims' => count($vec), 'vector' => $vec, 'provider' => 'ollama', 'model' => $model];
        }

        throw new \RuntimeException("Unknown embeddings provider: $provider");
    }
}
