<?php

namespace App\Http\Resources;

use App\Models\AiProvider;
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
        /** @var AiProvider $provider */
        $provider = $this->resource;

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
            'name' => $provider->name ?? $provider->provider,
            'enabled' => $provider->enabled,
            'status' => $provider->health_status['status'] ?? 'unknown',
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
            'ui_preferences' => $provider->ui_preferences ?? [],
            'created_at' => $provider->created_at->toISOString(),
            'updated_at' => $provider->updated_at->toISOString(),

            // Additional fields
            'display_name' => $provider->name ?? $provider->provider,
            'is_available' => $provider->enabled && $provider->credentials()->where('is_active', true)->exists(),
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

            // Provider metadata
            'metadata' => $provider->metadata,
            'logo_url' => $provider->logo_url,
        ];
    }
}
