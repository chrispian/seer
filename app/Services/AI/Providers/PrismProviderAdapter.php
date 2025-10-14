<?php

namespace App\Services\AI\Providers;

use App\Contracts\AIProviderInterface;
use App\Models\AICredential;
use App\Services\Telemetry\CorrelationContext;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;

class PrismProviderAdapter implements AIProviderInterface
{
    protected string $providerName;

    protected array $config;

    protected array $supportedOperations = ['text', 'embedding', 'streaming'];

    public function __construct(string $providerName, array $config = [])
    {
        $this->providerName = $providerName;
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->providerName;
    }

    public function supports(string $operation): bool
    {
        return in_array($operation, $this->supportedOperations);
    }

    public function authenticate(array $credentials = []): bool
    {
        return $this->isAvailable();
    }

    public function isAvailable(): bool
    {
        $storedCredential = AICredential::getActiveCredential($this->providerName);
        if ($storedCredential) {
            return true;
        }

        $configKey = "prism.providers.{$this->providerName}.api_key";
        $apiKey = config($configKey);

        return ! empty($apiKey);
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->getDefaultModel();
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        $topP = $options['top_p'] ?? null;
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $startTime = microtime(true);

        $request = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        if ($topP !== null) {
            $request['top_p'] = $topP;
        }

        try {
            $prismRequest = Prism::text()
                ->using($this->providerName, $model, $this->getProviderConfig())
                ->withMaxTokens($maxTokens)
                ->withPrompt($prompt);

            if ($temperature !== null) {
                $prismRequest->usingTemperature($temperature);
            }

            if ($topP !== null) {
                $prismRequest->usingTopP($topP);
            }

            $response = $prismRequest->asText();

            $responseData = [
                'text' => $response->text,
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'total_tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
                    'cached_tokens' => $response->usage->cacheReadInputTokens,
                ],
                'model' => $model,
                'provider' => $this->providerName,
                'finish_reason' => $response->finishReason->name,
            ];

            $this->logApiRequest('text_generation', $request, $responseData, null, $telemetryContext, $startTime);

            return $responseData;

        } catch (\Exception $e) {
            $this->logApiRequest('text_generation', $request, null, $e, $telemetryContext, $startTime);
            throw $e;
        }
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $model = $options['model'] ?? $this->getDefaultModel();
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        $topP = $options['top_p'] ?? null;

        try {
            $prismRequest = Prism::text()
                ->using($this->providerName, $model, $this->getProviderConfig())
                ->withMaxTokens($maxTokens)
                ->withMessages($this->convertMessagesToPrismFormat($messages));

            if ($temperature !== null) {
                $prismRequest->usingTemperature($temperature);
            }

            if ($topP !== null) {
                $prismRequest->usingTopP($topP);
            }

            foreach ($prismRequest->asStream() as $chunk) {
                yield $chunk->text;
            }

        } catch (\Exception $e) {
            Log::error('Prism streaming failed', [
                'provider' => $this->providerName,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function generateEmbedding(string $text, array $options = []): array
    {
        $model = $options['model'] ?? $this->getDefaultEmbeddingModel();
        $telemetryContext = $options['_telemetry_context'] ?? [];

        $startTime = microtime(true);

        $request = [
            'model' => $model,
            'input' => $text,
        ];

        try {
            $response = Prism::embeddings()
                ->using($this->providerName, $model, $this->getProviderConfig())
                ->withInput($text)
                ->asEmbedding();

            $responseData = [
                'embedding' => $response->embedding,
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'total_tokens' => $response->usage->promptTokens,
                ],
                'model' => $model,
                'provider' => $this->providerName,
            ];

            $this->logApiRequest('embedding_generation', $request, $responseData, null, $telemetryContext, $startTime);

            return $responseData;

        } catch (\Exception $e) {
            $this->logApiRequest('embedding_generation', $request, null, $e, $telemetryContext, $startTime);
            throw $e;
        }
    }

    public function healthCheck(): array
    {
        $startTime = microtime(true);

        try {
            if (! $this->isAvailable()) {
                return [
                    'status' => 'failed',
                    'provider' => $this->providerName,
                    'error' => 'Provider not properly configured',
                    'response_time_ms' => 0,
                ];
            }

            $response = Prism::text()
                ->using($this->providerName, $this->getDefaultModel(), $this->getProviderConfig())
                ->withMaxTokens(10)
                ->withPrompt('Hello')
                ->asText();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'success',
                'provider' => $this->providerName,
                'response_time_ms' => $responseTime,
                'tokens_used' => $response->usage->promptTokens + $response->usage->completionTokens,
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'failed',
                'provider' => $this->providerName,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ];
        }
    }

    public function getConfigRequirements(): array
    {
        return ['api_key'];
    }

    public function getAvailableModels(): array
    {
        return [
            'text_models' => array_keys($this->config['text_models'] ?? []),
            'embedding_models' => array_keys($this->config['embedding_models'] ?? []),
        ];
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    protected function getProviderConfig(): array
    {
        $config = [];

        $storedCredential = AICredential::getActiveCredential($this->providerName);
        if ($storedCredential) {
            $credentials = $storedCredential->getCredentials();
            $config['api_key'] = $credentials['key'] ?? $credentials['api_key'] ?? null;
        } else {
            $config['api_key'] = config("prism.providers.{$this->providerName}.api_key");
        }

        if ($this->providerName === 'openai') {
            $config['url'] = config('prism.providers.openai.url');
            $config['organization'] = config('prism.providers.openai.organization');
            $config['project'] = config('prism.providers.openai.project');
        } elseif ($this->providerName === 'anthropic') {
            $config['version'] = config('prism.providers.anthropic.version');
        } elseif ($this->providerName === 'ollama') {
            $config['url'] = config('prism.providers.ollama.url');
        } elseif ($this->providerName === 'openrouter') {
            $config['url'] = config('prism.providers.openrouter.url');
        }

        return array_filter($config);
    }

    protected function getDefaultModel(): string
    {
        return match ($this->providerName) {
            'openai' => 'gpt-4o-mini',
            'anthropic' => 'claude-3-5-haiku-20241022',
            'ollama' => 'llama2',
            'openrouter' => 'anthropic/claude-3.5-sonnet',
            default => 'gpt-4o-mini',
        };
    }

    protected function getDefaultEmbeddingModel(): string
    {
        return match ($this->providerName) {
            'openai' => 'text-embedding-3-small',
            'ollama' => 'nomic-embed-text',
            default => 'text-embedding-3-small',
        };
    }

    protected function convertMessagesToPrismFormat(array $messages): array
    {
        return array_map(function ($message) {
            return [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }, $messages);
    }

    protected function logApiRequest(
        string $operation,
        array $request,
        ?array $response = null,
        ?\Exception $error = null,
        array $context = [],
        ?float $startTime = null
    ): void {
        $startTime = $startTime ?? microtime(true);

        $logData = [
            'provider' => $this->providerName,
            'operation' => $operation,
            'correlation_id' => $context['correlation_id'] ?? CorrelationContext::get(),
            'user_id' => $context['user_id'] ?? auth()->id(),
            'session_id' => $context['session_id'] ?? null,
            'request_type' => $context['request_type'] ?? 'unknown',
            'model' => $request['model'] ?? null,
            'temperature' => $request['temperature'] ?? null,
            'top_p' => $request['top_p'] ?? null,
            'max_tokens' => $request['max_tokens'] ?? null,
            'message_count' => count($request['messages'] ?? []),
            'request_start_time' => $startTime,
            'timestamp' => now()->toISOString(),
        ];

        if ($response) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $logData = array_merge($logData, [
                'response_time_ms' => round($responseTime, 2),
                'success' => true,
                'tokens_prompt' => $response['usage']['prompt_tokens'] ?? null,
                'tokens_completion' => $response['usage']['completion_tokens'] ?? null,
                'tokens_total' => $response['usage']['total_tokens'] ?? null,
                'cost_usd' => $this->calculateCost($response['usage'] ?? [], $request['model'] ?? null),
            ]);
        }

        if ($error) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $logData = array_merge($logData, [
                'response_time_ms' => round($responseTime, 2),
                'success' => false,
                'error_message' => $error->getMessage(),
                'error_class' => get_class($error),
                'error_category' => $this->categorizeError($error),
            ]);
        }

        Log::info('AI Provider API call (Prism)', $logData);
    }

    protected function calculateCost(array $usage, ?string $model): ?float
    {
        if (empty($usage) || ! $model) {
            return null;
        }

        $costRates = config('fragments.models.cost_rates', []);
        $modelRates = $costRates[$model] ?? $costRates[$this->providerName]['default'] ?? null;

        if (! $modelRates) {
            return null;
        }

        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        $inputCost = ($inputTokens / 1000) * ($modelRates['input_per_thousand'] ?? 0);
        $outputCost = ($outputTokens / 1000) * ($modelRates['output_per_thousand'] ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    protected function categorizeError(\Exception $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'not found') || str_contains($message, 'not available')) {
            return 'provider_unavailable';
        }

        if (str_contains($message, 'auth') || str_contains($message, 'unauthorized') || str_contains($message, 'api key')) {
            return 'authentication_error';
        }

        if (str_contains($message, 'rate limit') || str_contains($message, 'quota') || str_contains($message, 'too many requests')) {
            return 'rate_limit_exceeded';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'connection') || str_contains($message, 'network')) {
            return 'network_error';
        }

        if (str_contains($message, 'model') || str_contains($message, 'parameter') || str_contains($message, 'invalid request')) {
            return 'request_error';
        }

        if (str_contains($message, 'server error') || str_contains($message, 'internal error') || str_contains($message, '500')) {
            return 'server_error';
        }

        return 'unknown_error';
    }
}
