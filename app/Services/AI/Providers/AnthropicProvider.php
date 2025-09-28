<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;

class AnthropicProvider extends AbstractAIProvider
{
    protected function getProviderName(): string
    {
        return 'anthropic';
    }

    protected function getSupportedOperations(): array
    {
        return ['text', 'streaming']; // Anthropic doesn't provide embeddings
    }

    public function getConfigRequirements(): array
    {
        return ['key'];
    }

    public function authenticate(array $credentials = []): bool
    {
        // Anthropic uses API key authentication, no special auth flow needed
        return $this->isAvailable();
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'claude-3-5-sonnet-latest';
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
            $baseUrl = $this->getConfigValue('base') ?? 'https://api.anthropic.com';
            $version = $this->getConfigValue('version') ?? '2023-06-01';

            $response = $this->createHttpClient()
                ->withHeaders([
                    'x-api-key' => $this->getConfigValue('key'),
                    'anthropic-version' => $version,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/v1/messages", $request);

            if ($response->failed()) {
                throw new \RuntimeException('Anthropic API request failed: '.$response->body());
            }

            $data = $response->json();
            $this->logApiRequest('text_generation', $request, $data);

            // Anthropic response format is different from OpenAI
            $content = '';
            if (isset($data['content']) && is_array($data['content'])) {
                foreach ($data['content'] as $contentBlock) {
                    if ($contentBlock['type'] === 'text') {
                        $content .= $contentBlock['text'];
                    }
                }
            }

            return [
                'text' => $content,
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
        throw new \RuntimeException('Anthropic does not support embedding generation');
    }

    protected function performHealthCheck(): array
    {
        try {
            $baseUrl = $this->getConfigValue('base') ?? 'https://api.anthropic.com';
            $version = $this->getConfigValue('version') ?? '2023-06-01';

            // Simple health check using a minimal text generation request
            $response = $this->createHttpClient()
                ->withHeaders([
                    'x-api-key' => $this->getConfigValue('key'),
                    'anthropic-version' => $version,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/v1/messages", [
                    'model' => 'claude-3-5-haiku-latest',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "OK" if you can read this.'],
                    ],
                    'max_tokens' => 10,
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'message' => 'Anthropic API is responding correctly',
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
        $model = $options['model'] ?? 'claude-3-5-sonnet-latest';
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;

        $request = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => true,
        ];

        try {
            $baseUrl = $this->getConfigValue('base') ?? 'https://api.anthropic.com';
            $version = $this->getConfigValue('version') ?? '2023-06-01';

            $response = Http::withOptions(['stream' => true, 'timeout' => 0])
                ->withHeaders([
                    'x-api-key' => $this->getConfigValue('key'),
                    'anthropic-version' => $version,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/v1/messages", $request);

            if ($response->failed()) {
                throw new \RuntimeException('Anthropic streaming request failed: '.$response->body());
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
                    $json = json_decode($data, true);
                    if (! is_array($json)) {
                        continue;
                    }

                    // Yield streaming content
                    if (($json['type'] ?? '') === 'content_block_delta' && isset($json['delta']['text'])) {
                        yield $json['delta']['text'];
                    }

                    // Check if stream is complete
                    if (($json['type'] ?? '') === 'message_stop') {
                        return $json; // Return final response with metadata
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logApiRequest('stream_chat', $request, null, $e);
            throw $e;
        }
    }
}
