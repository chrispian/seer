<?php

namespace App\Actions;

use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

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

            if (!$providerInstance) {
                throw new \RuntimeException("Provider '{$provider}' not found");
            }

            if (!$providerInstance->supportsStreaming()) {
                throw new \RuntimeException("Provider '{$provider}' does not support streaming");
            }

            if (!$providerInstance->isAvailable()) {
                throw new \RuntimeException("Provider '{$provider}' is not available");
            }

            Log::info('Starting chat stream', [
                'provider' => $provider,
                'model' => $options['model'] ?? 'default',
                'message_count' => count($messages),
            ]);

            // Stream the chat and collect deltas
            $streamGenerator = $providerInstance->streamChat($messages, $options);
            
            foreach ($streamGenerator as $delta) {
                $finalMessage .= $delta;
                $onDelta($delta);
            }

            // Get the final response from the generator return value
            $providerResponse = $streamGenerator->getReturn();
            
            $onComplete();

            Log::info('Chat stream completed', [
                'provider' => $provider,
                'message_length' => strlen($finalMessage),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return [
                'final_message' => $finalMessage,
                'provider_response' => $providerResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Chat streaming failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            throw $e;
        }
    }
}