<?php

namespace App\Http\Controllers;

use App\Models\AICredential;
use Illuminate\Http\JsonResponse;

class ModelController extends Controller
{
    /**
     * Get available models grouped by provider
     */
    public function available(): JsonResponse
    {
        $providers = config('fragments.models.providers', []);
        $availableModels = [];

        foreach ($providers as $providerKey => $provider) {
            // Check if provider has valid credentials
            $hasCredentials = $this->hasValidCredentials($providerKey, $provider['config_keys'] ?? []);

            if (! $hasCredentials) {
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

    /**
     * Check if provider has valid credentials
     */
    private function hasValidCredentials(string $providerKey, array $configKeys): bool
    {
        if (empty($configKeys)) {
            return false;
        }

        // Check if provider has active credentials
        $credential = AICredential::getActiveCredential($providerKey);

        if (! $credential) {
            return false;
        }

        // For additional validation, we could check if the credentials are properly configured
        // but for now, just checking if they exist is sufficient
        return true;
    }
}
