<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class OpenRouterProvider extends AbstractAIProvider
{
    protected function getProviderName(): string
    {
        return 'openrouter';
    }

    protected function getSupportedOperations(): array
    {
        return ['text', 'streaming']; // OpenRouter doesn't provide embeddings directly
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
        $topP = $options['top_p'] ?? null;

        // Extract telemetry context from options
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $request = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        // Add top_p if specified
        if ($topP !== null) {
            $request['top_p'] = $topP;
        }

        try {
            $response = $this->createHttpClient()
                ->withToken($this->getConfigValue('key'))
                ->post('https://openrouter.ai/api/v1/chat/completions', $request);

            $data = $response->json();
            $this->logApiRequest('text_generation', $request, $data, null, $telemetryContext);

            return [
                'text' => Arr::get($data, 'choices.0.message.content', ''),
                'usage' => $data['usage'] ?? null,
                'model' => $model,
                'provider' => $this->getName(),
            ];

        } catch (\Exception $e) {
            $this->logApiRequest('text_generation', $request, null, $e, $telemetryContext);
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

    /**
     * Check if provider supports streaming
     */
    public function supportsStreaming(): bool
    {
        return true;
    }

    /**
     * Stream chat completions with real-time deltas
     */
    public function streamChat(array $messages, array $options = []): \Generator
    {
        $model = $options['model'] ?? 'anthropic/claude-3.5-sonnet';
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;

        // Extract telemetry context from options
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $request = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => true,
        ];

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

            $response = Http::withOptions(['stream' => true, 'timeout' => 0])
                ->withHeaders($headers)
                ->post("{$baseUrl}/chat/completions", $request);

            if ($response->failed()) {
                throw new \RuntimeException('OpenRouter streaming request failed: '.$response->body());
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';

            while (! $body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '') {
                    usleep(50_000);

                    continue;
                }
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '' || ! str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $data = substr($line, 6); // Remove "data: " prefix
                    if ($data === '[DONE]') {
                        return; // Stream complete
                    }

                    $json = json_decode($data, true);
                    if (! is_array($json)) {
                        continue;
                    }

                    // Yield streaming content (OpenRouter uses OpenAI-compatible format)
                    if (isset($json['choices'][0]['delta']['content'])) {
                        yield $json['choices'][0]['delta']['content'];
                    }

                    // Check if stream is complete
                    if (isset($json['choices'][0]['finish_reason'])) {
                        return $json; // Return final response with metadata
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logApiRequest('stream_chat', $request, null, $e, $telemetryContext);
            throw $e;
        }
    }
}
