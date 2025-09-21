<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Arr;

class OpenRouterProvider extends AbstractAIProvider
{
    protected function getProviderName(): string
    {
        return 'openrouter';
    }

    protected function getSupportedOperations(): array
    {
        return ['text']; // OpenRouter doesn't provide embeddings directly
    }

    public function getConfigRequirements(): array
    {
        return ['key'];
    }

    public function authenticate(array $credentials = []): bool
    {
        // OpenRouter uses API key authentication, no special auth flow needed
        return $this->isAvailable();
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'anthropic/claude-3.5-sonnet';
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;

        $request = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        try {
            $baseUrl = $this->getConfigValue('base') ?? 'https://openrouter.ai/api/v1';

            $headers = [
                'Authorization' => 'Bearer '.$this->getConfigValue('key'),
                'Content-Type' => 'application/json',
            ];

            // Add optional headers for better routing and attribution
            if ($referer = $this->getConfigValue('referer')) {
                $headers['HTTP-Referer'] = $referer;
            }

            if ($title = $this->getConfigValue('title')) {
                $headers['X-Title'] = $title;
            }

            $response = $this->createHttpClient()
                ->withHeaders($headers)
                ->post("{$baseUrl}/chat/completions", $request);

            if ($response->failed()) {
                throw new \RuntimeException('OpenRouter API request failed: '.$response->body());
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
        throw new \RuntimeException('OpenRouter does not provide direct embedding generation');
    }

    protected function performHealthCheck(): array
    {
        try {
            $baseUrl = $this->getConfigValue('base') ?? 'https://openrouter.ai/api/v1';

            $headers = [
                'Authorization' => 'Bearer '.$this->getConfigValue('key'),
                'Content-Type' => 'application/json',
            ];

            // Add optional headers
            if ($referer = $this->getConfigValue('referer')) {
                $headers['HTTP-Referer'] = $referer;
            }

            if ($title = $this->getConfigValue('title')) {
                $headers['X-Title'] = $title;
            }

            // Simple health check using a minimal text generation request
            $response = $this->createHttpClient()
                ->withHeaders($headers)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'anthropic/claude-3.5-haiku',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "OK" if you can read this.'],
                    ],
                    'max_tokens' => 10,
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'message' => 'OpenRouter API is responding correctly',
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
