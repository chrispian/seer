<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle new Provider model structure
        if ($this->resource instanceof \App\Models\Provider || (is_array($this->resource) && isset($this->resource['config']) && $this->resource['config'] instanceof \App\Models\Provider)) {
            $provider = $this->resource instanceof \App\Models\Provider ? $this->resource : $this->resource['config'];
            $models = $provider->models->map(function ($model) use ($provider) {
                return [
                    'id' => $model->model_id,
                    'name' => $model->name,
                    'provider' => $provider->provider,
                    'capabilities' => $model->capabilities ?? [],
                    'context_length' => $model->limits['context_length'] ?? null,
                    'pricing' => $model->pricing ?? null,
                    'enabled' => $model->enabled,
                ];
            });

            // Extract all capabilities from models
            $allCapabilities = $models->pluck('capabilities')->flatten()->unique()->values();
            
            // Calculate model counts
            $totalModels = $models->count();
            $enabledModels = $models->where('enabled', true)->count();

            return [
                'id' => $provider->provider,
                'name' => $provider->name ?? $provider->getDisplayName(),
                'enabled' => $provider->enabled,
                'status' => $provider->getHealthStatus(),
                'capabilities' => $allCapabilities->toArray(),
                'models' => $models->toArray(),
                'model_counts' => [
                    'total' => $totalModels,
                    'enabled' => $enabledModels,
                    'disabled' => $totalModels - $enabledModels,
                ],
                'credentials_count' => $provider->credentials()->count(),
                'last_health_check' => $provider->last_health_check?->toISOString(),
                'usage_count' => $provider->usage_count,
                'ui_preferences' => $provider->getUIPreferences(),
                'created_at' => $provider->created_at->toISOString(),
                'updated_at' => $provider->updated_at->toISOString(),

                // Additional fields for extended functionality
                'display_name' => $provider->getDisplayName(),
                'is_available' => $provider->isAvailable(),
                'priority' => $provider->priority,

                // Statistics
                'stats' => [
                    'credential_count' => $provider->credentials()->count(),
                    'active_credential_count' => $provider->credentials()->where('is_active', true)->count(),
                    'usage_count' => $provider->usage_count,
                    'total_cost' => (float) $provider->total_cost,
                    'last_health_check' => $provider->last_health_check?->toISOString(),
                ],

                // Health details
                'health_details' => $provider->health_status,

                // Rate limiting info
                'rate_limits' => $provider->rate_limits,

                // Provider metadata from models.dev
                'metadata' => $provider->metadata,
                'logo_url' => $provider->logo_url,
            ];
        }

        // Legacy format support (keeping for backward compatibility)
        $config = $this->resource['config'];
        $capabilities = $this->resource['capabilities'];

        // Convert models to expected format
        $models = [];
        $allCapabilities = [];
        
        if (isset($capabilities['text_models'])) {
            foreach ($capabilities['text_models'] as $modelId => $modelData) {
                $models[] = [
                    'id' => $modelId,
                    'name' => $modelData['name'] ?? $modelId,
                    'provider' => $this->resource['name'],
                    'capabilities' => ['text'],
                    'context_length' => $modelData['context_length'] ?? null,
                ];
            }
            $allCapabilities[] = 'text';
        }
        
        if (isset($capabilities['embedding_models'])) {
            foreach ($capabilities['embedding_models'] as $modelId => $modelData) {
                $models[] = [
                    'id' => $modelId,
                    'name' => $modelData['name'] ?? $modelId,
                    'provider' => $this->resource['name'],
                    'capabilities' => ['embedding'],
                    'context_length' => $modelData['dimensions'] ?? null,
                ];
            }
            $allCapabilities[] = 'embedding';
        }

        if ($capabilities['supports_streaming'] ?? false) {
            $allCapabilities[] = 'streaming';
        }

        if ($capabilities['supports_function_calling'] ?? false) {
            $allCapabilities[] = 'function_calling';
        }

        return [
            'id' => $this->resource['name'],
            'name' => $config->getDisplayName(),
            'enabled' => $config->enabled,
            'status' => $this->resource['health_status'],
            'capabilities' => array_unique($allCapabilities),
            'models' => $models,
            'credentials_count' => $this->resource['credential_count'],
            'last_health_check' => $config->last_health_check?->toISOString(),
            'usage_count' => $config->usage_count,
            'ui_preferences' => $config->getUIPreferences(),
            'created_at' => $config->created_at->toISOString(),
            'updated_at' => $config->updated_at->toISOString(),

            // Additional fields for extended functionality
            'display_name' => $config->getDisplayName(),
            'is_available' => $this->resource['is_available'],
            'priority' => $config->priority,

            // Statistics
            'stats' => [
                'credential_count' => $this->resource['credential_count'],
                'active_credential_count' => $this->resource['active_credential_count'],
                'usage_count' => $config->usage_count,
                'total_cost' => (float) $config->total_cost,
                'last_health_check' => $config->last_health_check?->toISOString(),
            ],

            // Health details
            'health_details' => $config->health_status,

            // Rate limiting info
            'rate_limits' => $config->rate_limits,
        ];
    }
}
