<?php

namespace App\Services;

use App\Models\AiProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProviderManagementService
{
    /**
     * Get all providers with status and configuration
     */
    public function getAllProviders(): Collection
    {
        // Return Provider models with their models loaded (all models for display, not just enabled)
        return AiProvider::with(['models' => function ($query) {
            $query->orderBy('enabled', 'desc')->orderBy('priority', 'desc');
        }, 'credentials'])
            ->orderBy('priority', 'desc')
            ->orderBy('provider')
            ->get();
    }

    /**
     * Get specific provider with detailed information
     */
    public function getProvider(string $identifier): ?array
    {
        // Try to find provider by ID first, then by name or provider field
        $provider = null;

        // If identifier is numeric, try to find by ID
        if (is_numeric($identifier)) {
            $provider = AiProvider::with(['models', 'credentials'])->find($identifier);
        }

        // If not found, try by name or provider field
        if (! $provider) {
            $provider = AiProvider::with(['models', 'credentials'])
                ->where('name', $identifier)
                ->orWhere('provider', $identifier)
                ->first();
        }

        if (! $provider) {
            return null;
        }

        return [
            'name' => $provider->name,
            'display_name' => $provider->name,
            
            'capabilities' => $provider->capabilities ?? [],
            'credentials' => $provider->credentials ?? collect(),
            'active_credentials' => $provider->credentials->where('is_active', true) ?? collect(),
            'credential_count' => $provider->credentials->count(),
            'active_credential_count' => $provider->credentials->where('is_active', true)->count(),
            'health_status' => $provider->health_status ?? 'unknown',
            'is_available' => $provider->enabled,
            'ui_preferences' => $provider->ui_preferences ?? [],
            'models' => $provider->models ?? collect(),
            'usage_stats' => [
                'usage_count' => $provider->usage_count,
                'total_cost' => $provider->total_cost,
                'last_health_check' => $provider->last_health_check,
            ],
        ];
    }

    /**
     * Update provider configuration
     */
    public function updateProviderConfig(string $providerIdentifier, array $config): Provider
    {
        // Find provider by ID or name
        $provider = null;
        if (is_numeric($providerIdentifier)) {
            $provider = AiProvider::find($providerIdentifier);
        }

        if (! $provider) {
            $provider = AiProvider::where('name', $providerIdentifier)
                ->orWhere('provider', $providerIdentifier)
                ->first();
        }

        if (! $provider) {
            throw new \Exception("Provider '{$providerIdentifier}' not found");
        }

        $allowedFields = [
            'enabled',
            'ui_preferences',
            'capabilities',
            'rate_limits',
            'priority',
        ];

        $updateData = array_intersect_key($config, array_flip($allowedFields));

        $provider->update($updateData);

        Log::info('Provider configuration updated', [
            'provider' => $provider->name,
            'updated_fields' => array_keys($updateData),
        ]);

        return $provider->fresh();
    }

    /**
     * Toggle provider enabled/disabled state
     */
    public function toggleProvider(string $providerIdentifier): Provider
    {
        // Find provider by ID or name
        $provider = null;
        if (is_numeric($providerIdentifier)) {
            $provider = AiProvider::find($providerIdentifier);
        }

        if (! $provider) {
            $provider = AiProvider::where('name', $providerIdentifier)
                ->orWhere('provider', $providerIdentifier)
                ->first();
        }

        if (! $provider) {
            throw new \Exception("Provider '{$providerIdentifier}' not found");
        }

        $newState = ! $provider->enabled;
        $provider->update(['enabled' => $newState]);

        Log::info('Provider toggled', [
            'provider' => $provider->name,
            'enabled' => $newState,
        ]);

        return $provider->fresh();
    }

    /**
     * Mark providers as synced (used for models.dev sync tracking)
     */
    public function markProvidersSynced(?string $provider = null): array
    {
        $results = [];

        if ($provider) {
            $providerModel = AiProvider::where('provider', $provider)
                ->orWhere('name', $provider)
                ->orWhere('id', $provider)
                ->first();

            if (! $providerModel) {
                throw new \InvalidArgumentException("Provider '{$provider}' not found");
            }

            $providersToSync = [$providerModel];
        } else {
            $providersToSync = AiProvider::all();
        }

        foreach ($providersToSync as $providerModel) {
            $providerModel->markAsSynced();

            $results[$providerModel->name] = [
                'status' => 'synced',
                'synced_at' => $providerModel->synced_at,
                'provider_id' => $providerModel->id,
            ];

            Log::info('Provider sync timestamp updated', [
                'provider' => $providerModel->name,
                'synced_at' => $providerModel->synced_at,
            ]);
        }

        return $results;
    }

    /**
     * Get provider statistics for dashboard
     */
    public function getProviderStatistics(): array
    {
        $stats = AiProvider::getProviderStats();
        $providers = $this->getAllProviders();

        return array_merge($stats, [
            'by_status' => $providers->groupBy(function ($provider) {
                return $provider['health_status'];
            })->map->count(),
            'available_count' => $providers->where('is_available', true)->count(),
            'with_credentials' => $providers->where('credential_count', '>', 0)->count(),
        ]);
    }

    /**
     * Get providers filtered by availability and capabilities
     */
    public function getProvidersForOperation(string $operation): Collection
    {
        return $this->getAllProviders()
            ->filter(function ($provider) use ($operation) {
                if (! $provider['is_available']) {
                    return false;
                }

                $capabilities = $provider['capabilities'];

                return match ($operation) {
                    'text' => ! empty($capabilities['text_models']),
                    'embedding' => ! empty($capabilities['embedding_models']),
                    'streaming' => $capabilities['supports_streaming'] ?? false,
                    'function_calling' => $capabilities['supports_function_calling'] ?? false,
                    default => true,
                };
            });
    }

    /**
     * Validate provider configuration requirements
     */
    public function validateProviderRequirements(string $provider): array
    {
        $providerData = $this->getProvider($provider);

        if (! $providerData) {
            return [
                'valid' => false,
                'errors' => ["Provider '{$provider}' not found"],
            ];
        }

        $errors = [];
        $configKeys = $providerData['config_requirements'];

        // Check if provider is enabled
        if (! $provider->enabled) {
            $errors[] = 'Provider is disabled';
        }

        // Check for active credentials
        if ($providerData['active_credential_count'] === 0) {
            $errors[] = 'No active credentials configured';
        }

        // Check configuration requirements
        foreach ($configKeys as $key) {
            if (empty(config('services.'.strtolower($provider).'.'.strtolower(str_replace('_', '.', $key))))) {
                // For some providers like Ollama, check alternative configurations
                if ($provider === 'ollama' && $key === 'OLLAMA_BASE_URL') {
                    if (empty(config('services.ollama.base'))) {
                        $errors[] = "Configuration key '{$key}' is not set";
                    }
                } else {
                    // For most providers, this would be API keys stored as credentials
                    // We don't validate env vars for API keys since they're stored in database
                    if (! str_contains(strtolower($key), 'api_key')) {
                        $errors[] = "Configuration key '{$key}' is not set";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'provider' => $provider,
            'provider_model' => $provider,
        ];
    }
}
