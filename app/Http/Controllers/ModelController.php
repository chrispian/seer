<?php

namespace App\Http\Controllers;

use App\Services\AI\AIProviderManager;
use Illuminate\Http\JsonResponse;

class ModelController extends Controller
{
    /**
     * Get available models grouped by provider
     */
    public function available(): JsonResponse
    {
        $providers = config('fragments.models.providers', []);
        $providerManager = app(AIProviderManager::class);
        $availableModels = [];

        foreach ($providers as $providerKey => $provider) {
            // Check if provider is available (handles both database credentials and config/env)
            try {
                $providerInstance = $providerManager->getProvider($providerKey);
                if (! $providerInstance || ! $providerInstance->isAvailable()) {
                    continue;
                }
            } catch (\Exception $e) {
                // Provider not found or not properly configured
                continue;
            }

            $textModels = $provider['text_models'] ?? [];

            if (empty($textModels)) {
                continue;
            }

            $models = [];
            foreach ($textModels as $modelKey => $modelInfo) {
                $models[] = [
                    'value' => "{$providerKey}:{$modelKey}",
                    'label' => $modelInfo['name'] ?? $modelKey,
                    'model_key' => $modelKey,
                    'context_length' => $modelInfo['context_length'] ?? null,
                ];
            }

            // Sort models alphabetically by label
            usort($models, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

            $availableModels[] = [
                'provider' => $providerKey,
                'provider_name' => $provider['name'] ?? ucfirst($providerKey),
                'models' => $models,
            ];
        }

        // Sort providers alphabetically by name
        usort($availableModels, fn ($a, $b) => strcasecmp($a['provider_name'], $b['provider_name']));

        return response()->json([
            'success' => true,
            'data' => $availableModels,
        ]);
    }
}
