<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\ModelSelectionService;
use App\Services\ProviderManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModelController extends Controller
{
    protected ModelSelectionService $modelSelection;

    protected ProviderManagementService $providerService;

    public function __construct(
        ModelSelectionService $modelSelection,
        ProviderManagementService $providerService
    ) {
        $this->modelSelection = $modelSelection;
        $this->providerService = $providerService;
    }

    /**
     * Get all available models across all providers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $providers = $this->providerService->getAllProviders();
            $models = [];

            foreach ($providers as $providerName => $providerData) {
                if (! $providerData['is_available']) {
                    continue;
                }

                $capabilities = $providerData['capabilities'];

                // Add text models
                foreach ($capabilities['text_models'] ?? [] as $modelKey => $modelInfo) {
                    $models[] = [
                        'id' => "{$providerName}:{$modelKey}",
                        'provider' => $providerName,
                        'provider_display_name' => $providerData['config']->getDisplayName(),
                        'model' => $modelKey,
                        'name' => $modelInfo['name'] ?? $modelKey,
                        'type' => 'text',
                        'context_length' => $modelInfo['context_length'] ?? null,
                        'capabilities' => [
                            'text_generation' => true,
                            'streaming' => $capabilities['supports_streaming'] ?? false,
                            'function_calling' => $capabilities['supports_function_calling'] ?? false,
                        ],
                        'provider_enabled' => $providerData['config']->enabled,
                        'provider_health' => $providerData['health_status'],
                    ];
                }

                // Add embedding models
                foreach ($capabilities['embedding_models'] ?? [] as $modelKey => $modelInfo) {
                    $models[] = [
                        'id' => "{$providerName}:{$modelKey}",
                        'provider' => $providerName,
                        'provider_display_name' => $providerData['config']->getDisplayName(),
                        'model' => $modelKey,
                        'name' => $modelInfo['name'] ?? $modelKey,
                        'type' => 'embedding',
                        'dimensions' => $modelInfo['dimensions'] ?? null,
                        'capabilities' => [
                            'embedding_generation' => true,
                        ],
                        'provider_enabled' => $providerData['config']->enabled,
                        'provider_health' => $providerData['health_status'],
                    ];
                }
            }

            // Filter by type if requested
            if ($request->has('type')) {
                $type = $request->get('type');
                $models = array_filter($models, fn ($model) => $model['type'] === $type);
            }

            // Filter by provider if requested
            if ($request->has('provider')) {
                $provider = $request->get('provider');
                $models = array_filter($models, fn ($model) => $model['provider'] === $provider);
            }

            // Sort models
            $sortBy = $request->get('sort', 'provider');
            usort($models, function ($a, $b) use ($sortBy) {
                return match ($sortBy) {
                    'name' => strcmp($a['name'], $b['name']),
                    'type' => strcmp($a['type'], $b['type']),
                    'provider' => strcmp($a['provider'], $b['provider']),
                    default => strcmp($a['provider'], $b['provider']),
                };
            });

            return response()->json([
                'data' => array_values($models),
                'meta' => [
                    'total_count' => count($models),
                    'text_models' => count(array_filter($models, fn ($m) => $m['type'] === 'text')),
                    'embedding_models' => count(array_filter($models, fn ($m) => $m['type'] === 'embedding')),
                    'providers' => count(array_unique(array_column($models, 'provider'))),
                ],
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list models', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve models',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available models for a specific provider
     */
    public function providerModels(string $provider): JsonResponse
    {
        try {
            $providerData = $this->providerService->getProvider($provider);

            if (! $providerData) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Provider '{$provider}' not found",
                ], 404);
            }

            $capabilities = $providerData['capabilities'];
            $models = [];

            // Add text models
            foreach ($capabilities['text_models'] ?? [] as $modelKey => $modelInfo) {
                $models[] = [
                    'id' => "{$provider}:{$modelKey}",
                    'model' => $modelKey,
                    'name' => $modelInfo['name'] ?? $modelKey,
                    'type' => 'text',
                    'context_length' => $modelInfo['context_length'] ?? null,
                    'capabilities' => [
                        'text_generation' => true,
                        'streaming' => $capabilities['supports_streaming'] ?? false,
                        'function_calling' => $capabilities['supports_function_calling'] ?? false,
                    ],
                    'available' => $providerData['is_available'],
                ];
            }

            // Add embedding models
            foreach ($capabilities['embedding_models'] ?? [] as $modelKey => $modelInfo) {
                $models[] = [
                    'id' => "{$provider}:{$modelKey}",
                    'model' => $modelKey,
                    'name' => $modelInfo['name'] ?? $modelKey,
                    'type' => 'embedding',
                    'dimensions' => $modelInfo['dimensions'] ?? null,
                    'capabilities' => [
                        'embedding_generation' => true,
                    ],
                    'available' => $providerData['is_available'],
                ];
            }

            return response()->json([
                'data' => $models,
                'meta' => [
                    'provider' => $provider,
                    'provider_display_name' => $providerData['display_name'],
                    'provider_enabled' => $providerData['config']->enabled,
                    'provider_available' => $providerData['is_available'],
                    'provider_health' => $providerData['health_status'],
                    'total_models' => count($models),
                    'text_models' => count(array_filter($models, fn ($m) => $m['type'] === 'text')),
                    'embedding_models' => count(array_filter($models, fn ($m) => $m['type'] === 'embedding')),
                ],
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get provider models', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve provider models',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get model display information
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $provider = $request->get('provider');
            $model = $request->get('model');

            if (! $provider || ! $model) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Provider and model parameters are required',
                ], 400);
            }

            $displayInfo = $this->modelSelection->getModelDisplayInfo($provider, $model);
            $providerData = $this->providerService->getProvider($provider);

            if (! $providerData) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Provider '{$provider}' not found",
                ], 404);
            }

            $capabilities = $providerData['capabilities'];
            $modelInfo = null;

            // Find model in capabilities
            if (isset($capabilities['text_models'][$model])) {
                $modelInfo = $capabilities['text_models'][$model];
                $modelInfo['type'] = 'text';
            } elseif (isset($capabilities['embedding_models'][$model])) {
                $modelInfo = $capabilities['embedding_models'][$model];
                $modelInfo['type'] = 'embedding';
            }

            if (! $modelInfo) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Model '{$model}' not found for provider '{$provider}'",
                ], 404);
            }

            return response()->json([
                'data' => [
                    'id' => "{$provider}:{$model}",
                    'provider' => $provider,
                    'provider_display_name' => $displayInfo['provider_name'],
                    'model' => $model,
                    'name' => $displayInfo['model_name'],
                    'type' => $modelInfo['type'],
                    'context_length' => $modelInfo['context_length'] ?? null,
                    'dimensions' => $modelInfo['dimensions'] ?? null,
                    'capabilities' => [
                        'text_generation' => $modelInfo['type'] === 'text',
                        'embedding_generation' => $modelInfo['type'] === 'embedding',
                        'streaming' => $capabilities['supports_streaming'] ?? false,
                        'function_calling' => $capabilities['supports_function_calling'] ?? false,
                    ],
                    'provider_status' => [
                        'enabled' => $providerData['config']->enabled,
                        'available' => $providerData['is_available'],
                        'health' => $providerData['health_status'],
                    ],
                ],
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get model details', [
                'provider' => $request->get('provider'),
                'model' => $request->get('model'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve model details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get model selection recommendations
     */
    public function recommendations(Request $request): JsonResponse
    {
        try {
            $context = $request->all();

            // Get text model recommendation
            $textSelection = $this->modelSelection->selectTextModel($context);

            // Get embedding model recommendation
            $embeddingSelection = $this->modelSelection->selectEmbeddingModel($context);

            return response()->json([
                'data' => [
                    'text_model' => $textSelection,
                    'embedding_model' => $embeddingSelection,
                    'context' => $context,
                ],
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get model recommendations', [
                'context' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get model recommendations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a model's configuration
     */
    public function updateModel(Request $request, \App\Models\AIModel $model): JsonResponse
    {
        try {
            $validated = $request->validate([
                'enabled' => 'sometimes|boolean',
                'priority' => 'sometimes|integer|min:1|max:100',
            ]);

            $model->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $model->fresh(),
                'message' => 'Model updated successfully',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update model', [
                'model_id' => $model->id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update model',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
