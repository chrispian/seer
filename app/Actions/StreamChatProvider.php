<?php

namespace App\Actions;

use App\Services\AI\AIProviderManager;
use App\Services\Telemetry\CorrelationContext;

class StreamChatProvider
{
    public function __invoke(
        string $provider,
        array $messages,
        array $options,
        callable $onDelta,
        callable $onComplete
    ): array {
        $startTime = microtime(true);
        $finalMessage = '';
        $providerResponse = null;

        try {
            $providerManager = app(AIProviderManager::class);
            $providerInstance = $providerManager->getProvider($provider);

            if (! $providerInstance) {
                throw new \RuntimeException("Provider '{$provider}' not found");
            }

            if (! $providerInstance->supportsStreaming()) {
                throw new \RuntimeException("Provider '{$provider}' does not support streaming");
            }

            if (! $providerInstance->isAvailable()) {
                throw new \RuntimeException("Provider '{$provider}' is not available");
            }

            // Use structured telemetry instead of basic logging
            CorrelationContext::addContext('provider', $provider);
            CorrelationContext::addContext('model', $options['model'] ?? 'default');

            // Stream the chat and collect deltas
            $streamGenerator = $providerInstance->streamChat($messages, $options);

            foreach ($streamGenerator as $delta) {
                $finalMessage .= $delta;
                $onDelta($delta);
            }

            // Get the final response from the generator return value
            $providerResponse = $streamGenerator->getReturn();

            $onComplete();

            // Telemetry is handled by the caller (ChatApiController) for better context

            return [
                'final_message' => $finalMessage,
                'provider_response' => $providerResponse,
            ];

        } catch (\Exception $e) {
            // Enhanced error categorization for better debugging
            $errorCategory = $this->categorizeError($e);
            $isRetryable = $this->isRetryableError($e);
            
            CorrelationContext::addContext('error_category', $errorCategory);
            CorrelationContext::addContext('is_retryable', $isRetryable);
            CorrelationContext::addContext('error_class', get_class($e));
            
            // Re-throw with enhanced context for caller telemetry
            throw $e;
        }
    }
    
    /**
     * Categorize errors for better debugging and monitoring
     */
    private function categorizeError(\Exception $e): string
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
     * Determine if an error is retryable
     */
    private function isRetryableError(\Exception $e): bool
    {
        $category = $this->categorizeError($e);
        
        // Retryable categories
        return in_array($category, [
            'network_error',
            'server_error',
            'rate_limit_exceeded', // with backoff
        ]);
    }
}
