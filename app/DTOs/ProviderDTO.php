<?php

namespace App\DTOs;

use App\Models\AiProvider;
use Illuminate\Support\Collection;

/**
 * Provider Data Transfer Object
 * 
 * Single source of truth for provider data structure.
 * All provider data should flow through this DTO to ensure consistency.
 */
class ProviderDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $displayName,
        public readonly bool $enabled,
        public readonly string $healthStatus,
        public readonly bool $isAvailable,
        public readonly array $capabilities,
        public readonly Collection $models,
        public readonly Collection $credentials,
        public readonly Collection $activeCredentials,
        public readonly int $credentialCount,
        public readonly int $activeCredentialCount,
        public readonly array $uiPreferences,
        public readonly array $usageStats,
        public readonly ?string $logoUrl = null,
        public readonly ?array $metadata = null,
        public readonly ?array $rateLimits = null,
    ) {}

    /**
     * Create DTO from AiProvider model
     */
    public static function fromModel(AiProvider $provider): self
    {
        return new self(
            name: $provider->provider,
            displayName: $provider->name ?? $provider->provider,
            enabled: $provider->enabled,
            healthStatus: $provider->health_status['status'] ?? 'unknown',
            isAvailable: $provider->enabled && $provider->credentials()->where('is_active', true)->exists(),
            capabilities: $provider->capabilities ?? [],
            models: $provider->models ?? collect(),
            credentials: $provider->credentials ?? collect(),
            activeCredentials: $provider->credentials->where('is_active', true) ?? collect(),
            credentialCount: $provider->credentials->count(),
            activeCredentialCount: $provider->credentials()->where('is_active', true)->count(),
            uiPreferences: $provider->ui_preferences ?? [],
            usageStats: [
                'usage_count' => $provider->usage_count,
                'total_cost' => $provider->total_cost,
                'last_health_check' => $provider->last_health_check,
            ],
            logoUrl: $provider->logo_url,
            metadata: $provider->metadata,
            rateLimits: $provider->rate_limits,
        );
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'enabled' => $this->enabled,
            'health_status' => $this->healthStatus,
            'is_available' => $this->isAvailable,
            'capabilities' => $this->capabilities,
            'models' => $this->models,
            'credentials' => $this->credentials,
            'active_credentials' => $this->activeCredentials,
            'credential_count' => $this->credentialCount,
            'active_credential_count' => $this->activeCredentialCount,
            'ui_preferences' => $this->uiPreferences,
            'usage_stats' => $this->usageStats,
            'logo_url' => $this->logoUrl,
            'metadata' => $this->metadata,
            'rate_limits' => $this->rateLimits,
        ];
    }

    /**
     * Get JSON representation
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
