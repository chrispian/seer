<?php

namespace App\Actions;

use App\Services\AI\AIProviderManager;

class ValidateStreamingProvider
{
    public function __invoke(string $provider): array
    {
        $providerManager = app(AIProviderManager::class);
        $providerInstance = $providerManager->getProvider($provider);

        if (!$providerInstance) {
            abort(400, "Provider '{$provider}' is not supported");
        }

        if (!$providerInstance->supportsStreaming()) {
            abort(400, "Provider '{$provider}' does not support streaming");
        }

        if (!$providerInstance->isAvailable()) {
            abort(400, "Provider '{$provider}' is not available or not configured");
        }

        return [
            'provider' => $provider,
            'supports_streaming' => true,
            'is_available' => true,
        ];
    }
}
