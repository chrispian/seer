<?php

namespace App\Services\AI\Providers;

use App\Contracts\AIProviderInterface;
use App\Services\Telemetry\CorrelationContext;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractAIProvider implements AIProviderInterface
{
    protected array $config;

    protected string $name;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->name = $this->getProviderName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function supports(string $operation): bool
    {
        return in_array($operation, $this->getSupportedOperations());
    }

    public function isAvailable(): bool
    {
        // First check if stored credentials are available
        $storedCredential = \App\Models\AiCredential::getActiveCredential($this->getName());
        if ($storedCredential) {
            return true;
        }

        // Fallback to checking configuration/environment
        foreach ($this->getConfigRequirements() as $requirement) {
            if (empty($this->getConfigValue($requirement))) {
                return false;
            }
        }

        return true;
    }

    public function healthCheck(): array
    {
        $startTime = microtime(true);

        try {
            if (! $this->isAvailable()) {
                return [
                    'status' => 'failed',
                    'provider' => $this->getName(),
                    'error' => 'Provider not properly configured',
                    'response_time_ms' => 0,
                ];
            }

            $result = $this->performHealthCheck();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return array_merge($result, [
                'provider' => $this->getName(),
                'response_time_ms' => $responseTime,
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error("Health check failed for provider {$this->getName()}", [
                'error' => $e->getMessage(),
                'provider' => $this->getName(),
            ]);

            return [
                'status' => 'failed',
                'provider' => $this->getName(),
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ];
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'text_models' => array_keys($this->config['text_models'] ?? []),
            'embedding_models' => array_keys($this->config['embedding_models'] ?? []),
        ];
    }

    /**
     * Create a configured HTTP client with common settings
     */
    protected function createHttpClient(): PendingRequest
    {
        return Http::timeout(30)
            ->retry(3, 100)
            ->withOptions([
                'verify' => true,
            ]);
    }

    /**
     * Get configuration value with fallback to stored credentials and environment
     */
    protected function getConfigValue(string $key): ?string
    {
        // First check stored credentials
        $storedCredential = \App\Models\AiCredential::getActiveCredential($this->getName());
        if ($storedCredential) {
            $credentials = $storedCredential->getCredentials();
            if (isset($credentials[$key])) {
                return $credentials[$key];
            }
        }

        // Then check direct config
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        // Then check services config
        $serviceKey = strtolower($this->getName());
        $configPath = "services.{$serviceKey}.{$key}";
        $value = config($configPath);
        if ($value !== null) {
            return $value;
        }

        // Finally check environment
        return env($key);
    }

    /**
     * Log API request for debugging and monitoring with enhanced telemetry
     */
    protected function logApiRequest(
        string $operation,
        array $request,
        ?array $response = null,
        ?\Exception $error = null,
        array $context = []
    ): void {
        $startTime = $context['start_time'] ?? microtime(true);

        $logData = [
            // Provider and operation identification
            'provider' => $this->getName(),
            'operation' => $operation,

            // Who (User/Context Tracking)
            'correlation_id' => $context['correlation_id'] ?? CorrelationContext::get(),
            'user_id' => $context['user_id'] ?? auth()->id(),
            'session_id' => $context['session_id'] ?? null,
            'request_type' => $context['request_type'] ?? 'unknown',

            // What (Content & Parameters)
            'model' => $request['model'] ?? null,
            'temperature' => $request['temperature'] ?? null,
            'top_p' => $request['top_p'] ?? null,
            'max_tokens' => $request['max_tokens'] ?? null,
            'prompt_length_chars' => strlen($this->extractPromptContent($request)),
            'message_count' => count($request['messages'] ?? []),

            // When (Timing Details)
            'request_start_time' => $startTime,
            'queue_wait_time_ms' => $context['queue_wait_ms'] ?? 0,
            'timestamp' => now()->toISOString(),

            // Request/Response metadata
            'request_size' => strlen(json_encode($request)),
        ];

        // Add response metrics if successful
        if ($response) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $logData = array_merge($logData, [
                'response_size' => strlen(json_encode($response)),
                'response_time_ms' => round($responseTime, 2),
                'success' => true,
                'http_status_code' => $response['http_status'] ?? null,

                // Token usage and cost
                'tokens_prompt' => $response['usage']['prompt_tokens'] ?? null,
                'tokens_completion' => $response['usage']['completion_tokens'] ?? null,
                'tokens_cached' => $response['usage']['cached_tokens'] ?? null,
                'cost_usd' => $this->calculateCost($response['usage'] ?? [], $request['model'] ?? null),
            ]);
        }

        // Add error information if failed
        if ($error) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $logData = array_merge($logData, [
                'response_time_ms' => round($responseTime, 2),
                'success' => false,
                'error_message' => $error->getMessage(),
                'error_class' => get_class($error),
                'error_category' => $this->categorizeError($error),
                'retry_count' => $context['retry_count'] ?? 0,
            ]);
        }

        Log::info('AI Provider API call', $logData);
    }

    /**
     * Get the provider name (to be implemented by concrete classes)
     */
    abstract protected function getProviderName(): string;

    /**
     * Get supported operations (to be implemented by concrete classes)
     */
    abstract protected function getSupportedOperations(): array;

    /**
     * Perform provider-specific health check
     */
    abstract protected function performHealthCheck(): array;

    /**
     * Extract prompt content from request for length calculation
     */
    protected function extractPromptContent(array $request): string
    {
        // Handle different request formats
        if (isset($request['messages'])) {
            // Chat completions format
            $content = '';
            foreach ($request['messages'] as $message) {
                $content .= $message['content'] ?? '';
            }

            return $content;
        }

        if (isset($request['prompt'])) {
            // Legacy completions format
            return $request['prompt'];
        }

        if (isset($request['input'])) {
            // Embeddings format
            return is_array($request['input']) ? implode(' ', $request['input']) : $request['input'];
        }

        return '';
    }

    /**
     * Categorize errors for better analytics
     */
    protected function categorizeError(\Exception $e): string
    {
        $message = strtolower($e->getMessage());

        // Provider availability issues
        if (str_contains($message, 'not found') || str_contains($message, 'not available')) {
            return 'provider_unavailable';
        }

        // Authentication/authorization issues
        if (str_contains($message, 'auth') || str_contains($message, 'unauthorized') || str_contains($message, 'api key')) {
            return 'authentication_error';
        }

        // Rate limiting
        if (str_contains($message, 'rate limit') || str_contains($message, 'quota') || str_contains($message, 'too many requests')) {
            return 'rate_limit_exceeded';
        }

        // Network/connectivity issues
        if (str_contains($message, 'timeout') || str_contains($message, 'connection') || str_contains($message, 'network')) {
            return 'network_error';
        }

        // Model/parameter issues
        if (str_contains($message, 'model') || str_contains($message, 'parameter') || str_contains($message, 'invalid request')) {
            return 'request_error';
        }

        // Server-side issues
        if (str_contains($message, 'server error') || str_contains($message, 'internal error') || str_contains($message, '500')) {
            return 'server_error';
        }

        return 'unknown_error';
    }

    /**
     * Calculate cost based on token usage and model
     */
    protected function calculateCost(array $usage, ?string $model): ?float
    {
        if (empty($usage) || ! $model) {
            return null;
        }

        // Get cost rates from configuration
        $costRates = config('fragments.models.cost_rates', []);

        // Try to find model-specific rates, fall back to provider defaults
        $modelRates = $costRates[$model] ?? null;
        if (! $modelRates) {
            // Extract provider from model name (e.g., 'gpt-4' -> 'openai')
            $provider = $this->getName();
            $modelRates = $costRates[$provider]['default'] ?? null;
        }

        if (! $modelRates) {
            return null;
        }

        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        $inputCost = ($inputTokens / 1000) * ($modelRates['input_per_thousand'] ?? 0);
        $outputCost = ($outputTokens / 1000) * ($modelRates['output_per_thousand'] ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Default streaming implementation - providers should override this
     */
    public function streamChat(array $messages, array $options = []): \Generator
    {
        throw new \RuntimeException("Streaming not implemented for provider: {$this->getName()}");
    }

    /**
     * Check if provider supports streaming - default to false
     */
    public function supportsStreaming(): bool
    {
        return false;
    }
}
