<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider extends AbstractAIProvider
{
    protected function getProviderName(): string
    {
        return 'ollama';
    }

    protected function getSupportedOperations(): array
    {
        return ['text', 'embedding', 'streaming'];
    }

    public function getConfigRequirements(): array
    {
        return ['base']; // Ollama requires base URL
    }

    public function authenticate(array $credentials = []): bool
    {
        // Ollama typically doesn't require authentication
        return $this->isAvailable();
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'llama3:latest';

        // Extract telemetry context from options
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $request = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ];

        try {
            $response = $this->createHttpClient()
                ->post($this->getEndpointUrl(), $request);

            $data = $response->json();
            $this->logApiRequest('text_generation', $request, $data, null, $telemetryContext);

            return [
                'text' => $data['response'] ?? '',
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
        $model = $options['model'] ?? 'nomic-embed-text';

        // Extract telemetry context from options
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $request = [
            'model' => $model,
            'prompt' => $text,
        ];

        try {
            $response = $this->createHttpClient()
                ->post($this->getEmbeddingEndpointUrl(), $request);

            $data = $response->json();
            $this->logApiRequest('embedding_generation', $request, $data, null, $telemetryContext);

            $vector = $data['embedding'] ?? [];

            return [
                'vector' => $vector,
                'dims' => count($vector),
                'model' => $model,
                'provider' => $this->getName(),
            ];

        } catch (\Exception $e) {
            $this->logApiRequest('embedding_generation', $request, null, $e, $telemetryContext);
            throw $e;
        }
    }

    protected function performHealthCheck(): array
    {
        try {
            $baseUrl = rtrim($this->getConfigValue('base') ?? 'http://127.0.0.1:11434', '/');

            // Check if Ollama is running by hitting the version endpoint
            $response = $this->createHttpClient()
                ->get("{$baseUrl}/api/version");

            if ($response->successful()) {
                $versionData = $response->json();

                return [
                    'status' => 'healthy',
                    'message' => 'Ollama is running',
                    'version' => $versionData['version'] ?? 'unknown',
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
     * Get list of available models from Ollama
     */
    public function getInstalledModels(): array
    {
        try {
            $baseUrl = rtrim($this->getConfigValue('base') ?? 'http://127.0.0.1:11434', '/');

            $response = $this->createHttpClient()
                ->get("{$baseUrl}/api/tags");

            if ($response->successful()) {
                $data = $response->json();

                return $data['models'] ?? [];
            }

            return [];

        } catch (\Exception $e) {
            Log::warning('Failed to get Ollama installed models', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get the endpoint URL for text generation
     */
    protected function getEndpointUrl(): string
    {
        $baseUrl = rtrim($this->getConfigValue('base') ?? 'http://127.0.0.1:11434', '/');
        return "{$baseUrl}/api/generate";
    }

    /**
     * Get the endpoint URL for embeddings
     */
    protected function getEmbeddingEndpointUrl(): string
    {
        $baseUrl = rtrim($this->getConfigValue('base') ?? 'http://127.0.0.1:11434', '/');
        return "{$baseUrl}/api/embeddings";
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
        $model = $options['model'] ?? 'llama3:latest';
        $temperature = $options['temperature'] ?? 0.7;
        $topP = $options['top_p'] ?? null;
        $maxTokens = $options['max_tokens'] ?? null;

        // Extract telemetry context from options
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $request = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
            'options' => [
                'temperature' => $temperature,
            ],
        ];

        // Add top_p if specified
        if ($topP !== null) {
            $request['options']['top_p'] = $topP;
        }

        // Add num_predict if max_tokens specified
        if ($maxTokens !== null) {
            $request['options']['num_predict'] = $maxTokens;
        }

        try {
            $baseUrl = rtrim($this->getConfigValue('base') ?? 'http://127.0.0.1:11434', '/');

            $response = Http::withOptions(['stream' => true, 'timeout' => 0])
                ->post("{$baseUrl}/api/chat", $request);

            if ($response->failed()) {
                throw new \RuntimeException('Ollama streaming request failed: '.$response->body());
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

                    if ($line === '') {
                        continue;
                    }

                    $json = json_decode($line, true);
                    if (! is_array($json)) {
                        continue;
                    }

                    // Yield streaming content
                    if (isset($json['message']['content'])) {
                        yield $json['message']['content'];
                    }

                    // Check if stream is complete
                    if (($json['done'] ?? false) === true) {
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
