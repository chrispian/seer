<?php

namespace App\Services\AI\Providers;

use App\Contracts\AIProviderInterface;
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
        $storedCredential = \App\Models\AICredential::getActiveCredential($this->getName());
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
        $storedCredential = \App\Models\AICredential::getActiveCredential($this->getName());
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
     * Log API request for debugging and monitoring
     */
    protected function logApiRequest(string $operation, array $request, ?array $response = null, ?\Exception $error = null): void
    {
        $logData = [
            'provider' => $this->getName(),
            'operation' => $operation,
            'request_size' => strlen(json_encode($request)),
            'timestamp' => now()->toISOString(),
        ];

        if ($response) {
            $logData['response_size'] = strlen(json_encode($response));
            $logData['success'] = true;
        }

        if ($error) {
            $logData['error'] = $error->getMessage();
            $logData['success'] = false;
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
