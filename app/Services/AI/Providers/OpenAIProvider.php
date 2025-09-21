<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Arr;

class OpenAIProvider extends AbstractAIProvider
{
    protected function getProviderName(): string
    {
        return 'openai';
    }

    protected function getSupportedOperations(): array
    {
        return ['text', 'embedding'];
    }

    public function getConfigRequirements(): array
    {
        return ['key'];
    }

    public function authenticate(array $credentials = []): bool
    {
        // OpenAI uses API key authentication, no special auth flow needed
        return $this->isAvailable();
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'gpt-4o-mini';
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        $topP = $options['top_p'] ?? null;

        $request = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        // Add top_p if specified (OpenAI supports this parameter)
        if ($topP !== null) {
            $request['top_p'] = $topP;
        }

        try {
            $response = $this->createHttpClient()
                ->withToken($this->getConfigValue('key'))
                ->post('https://api.openai.com/v1/chat/completions', $request);

            if ($response->failed()) {
                throw new \RuntimeException('OpenAI API request failed: '.$response->body());
            }

            $data = $response->json();
            $this->logApiRequest('text_generation', $request, $data);

            return [
                'text' => Arr::get($data, 'choices.0.message.content', ''),
                'usage' => $data['usage'] ?? null,
                'model' => $model,
                'provider' => $this->getName(),
            ];

        } catch (\Exception $e) {
            $this->logApiRequest('text_generation', $request, null, $e);
            throw $e;
        }
    }

    public function generateEmbedding(string $text, array $options = []): array
    {
        $model = $options['model'] ?? 'text-embedding-3-small';

        $request = [
            'model' => $model,
            'input' => $text,
        ];

        try {
            $response = $this->createHttpClient()
                ->withToken($this->getConfigValue('key'))
                ->post('https://api.openai.com/v1/embeddings', $request);

            if ($response->failed()) {
                throw new \RuntimeException('OpenAI embeddings API request failed: '.$response->body());
            }

            $data = $response->json();
            $this->logApiRequest('embedding_generation', $request, $data);

            $vector = Arr::get($data, 'data.0.embedding', []);

            return [
                'vector' => $vector,
                'dims' => count($vector),
                'model' => $model,
                'provider' => $this->getName(),
                'usage' => $data['usage'] ?? null,
            ];

        } catch (\Exception $e) {
            $this->logApiRequest('embedding_generation', $request, null, $e);
            throw $e;
        }
    }

    protected function performHealthCheck(): array
    {
        try {
            // Simple health check using a minimal embedding request
            $response = $this->createHttpClient()
                ->withToken($this->getConfigValue('key'))
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-3-small',
                    'input' => 'health check',
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'message' => 'OpenAI API is responding correctly',
                ];
            } else {
                return [
                    'status' => 'failed',
                    'error' => "HTTP {$response->status()}: {$response->body()}",
                ];
            }

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
