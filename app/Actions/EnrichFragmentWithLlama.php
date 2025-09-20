<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Services\AI\ModelSelectionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichFragmentWithLlama
{
    protected ModelSelectionService $modelSelection;

    public function __construct(ModelSelectionService $modelSelection)
    {
        $this->modelSelection = $modelSelection;
    }

    public function __invoke(Fragment $fragment): ?Fragment
    {
        Log::debug('EnrichFragmentWithLlama::invoke()');

        // Build context for model selection
        $context = [
            'operation_type' => 'text',
            'command' => 'enrich_fragment',
            'vault' => $fragment->vault,
            'project_id' => $fragment->project_id,
        ];

        // Select appropriate model
        $selectedModel = $this->modelSelection->selectTextModel($context);

        $prompt = <<<PROMPT
Given the following user input, return a structured fragment in JSON.

Input:
{$fragment->message}

Output format:
{
  "type": "log",
  "message": "...",
  "tags": ["tag1", "tag2"],
  "metadata": {
    "confidence": 0.9
  },
  "state": {
    "status": "open"
  },
  "vault": "default"
}
Only return JSON. No markdown, no explanation.
PROMPT;

        $response = $this->makeApiCall($selectedModel, $prompt);

        if (! $response || ! $response->ok()) {
            Log::error('AI enrichment failed', [
                'fragment_id' => $fragment->id,
                'provider' => $selectedModel['provider'],
                'model' => $selectedModel['model'],
            ]);

            return null;
        }

        $raw = $this->extractResponse($response, $selectedModel['provider']);
        $cleanJson = $this->cleanJsonResponse($raw);
        $parsed = json_decode($cleanJson, true);

        if (! is_array($parsed)) {
            Log::error('JSON decode failed', [
                'raw' => $raw,
                'cleanJson' => $cleanJson,
                'provider' => $selectedModel['provider'],
                'model' => $selectedModel['model'],
            ]);

            return null;
        }

        // Save enrichment with model metadata
        $fragment->metadata = array_merge((array) $fragment->metadata, [
            'enrichment' => $parsed,
        ]);

        if (! empty($parsed['type'])) {
            // Find type by value and set both type string and type_id
            $typeModel = \App\Models\Type::where('value', $parsed['type'])->first();
            if ($typeModel) {
                $fragment->type = $parsed['type'];
                $fragment->type_id = $typeModel->id;
            }
        }

        // Store model metadata
        $fragment->model_provider = $selectedModel['provider'];
        $fragment->model_name = $selectedModel['model'];

        $fragment->save();

        Log::info('Fragment enriched with AI', [
            'fragment_id' => $fragment->id,
            'provider' => $selectedModel['provider'],
            'model' => $selectedModel['model'],
        ]);

        return $fragment;
    }

    protected function makeApiCall(array $selectedModel, string $prompt)
    {
        $provider = $selectedModel['provider'];
        $model = $selectedModel['model'];

        if ($provider === 'ollama') {
            $base = rtrim(config('services.ollama.base', 'http://127.0.0.1:11434'), '/');

            return Http::timeout(20)->post("$base/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ]);
        }

        if ($provider === 'openai') {
            $apiKey = config('services.openai.key');

            return Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.3,
                ]);
        }

        throw new \RuntimeException("Unsupported provider for enrichment: $provider");
    }

    protected function extractResponse($response, string $provider): string
    {
        if ($provider === 'ollama') {
            return $response->json('response');
        }

        if ($provider === 'openai') {
            return $response->json('choices.0.message.content');
        }

        return '';
    }

    protected function cleanJsonResponse(string $raw): string
    {
        return preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $raw, $matches)
            ? $matches[1]
            : (str_starts_with(trim($raw), 'Here') ? explode('```', $raw)[1] ?? $raw : $raw);
    }
}
