<?php

namespace App\Http\Controllers;

use App\Services\ProviderManagementService;
use Illuminate\Http\JsonResponse;

class ModelController extends Controller
{
    protected ProviderManagementService $providerService;

    public function __construct(ProviderManagementService $providerService)
    {
        $this->providerService = $providerService;
    }

    /**
     * Get available models grouped by provider
     */
    public function available(): JsonResponse
    {
        try {
            $providers = $this->providerService->getAllProviders();
            $availableModels = [];

            foreach ($providers as $provider) {
                // Only include enabled providers with models
                if (! $provider->enabled || $provider->models->isEmpty()) {
                    continue;
                }

                $models = [];
                foreach ($provider->models as $model) {
                    // Only include enabled models
                    if (! $model->enabled) {
                        continue;
                    }

                    // Get context length from limits JSON
                    $limits = is_string($model->limits) ? json_decode($model->limits, true) : $model->limits;
                    $contextLength = $limits['context_length'] ?? null;

                    $models[] = [
                        'id' => $model->id,  // Primary key for new FK-based approach
                        'value' => "{$provider->id}/{$model->model_id}",  // Legacy for backward compatibility
                        'label' => $model->name,
                        'model_key' => $model->model_id,
                        'context_length' => $contextLength,
                    ];
                }

                if (empty($models)) {
                    continue;
                }

                // Sort models alphabetically by label
                usort($models, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

                $availableModels[] = [
                    'provider' => $provider->id,
                    'provider_name' => $provider->name,
                    'models' => $models,
                ];
            }

            // Sort providers alphabetically by name
            usort($availableModels, fn ($a, $b) => strcasecmp($a['provider_name'], $b['provider_name']));

            return response()->json([
                'success' => true,
                'data' => $availableModels,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get available models', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load available models',
                'data' => [],
            ], 500);
        }
    }
}
