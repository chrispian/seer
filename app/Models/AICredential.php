<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AICredential extends Model
{
    protected $fillable = [
        'provider',
        'credential_type',
        'encrypted_credentials',
        'metadata',
        'ui_metadata',
        'provider_config_id',
        'expires_at',
        'is_active',
        'last_used_at',
        'usage_count',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'ui_metadata' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'usage_count' => 'integer',
            'total_cost' => 'decimal:6',
        ];
    }

    /**
     * Set credentials with encryption
     */
    public function setCredentials(array $credentials): void
    {
        $this->encrypted_credentials = Crypt::encrypt(json_encode($credentials));
    }

    /**
     * Get decrypted credentials
     */
    public function getCredentials(): array
    {
        try {
            $decrypted = Crypt::decrypt($this->encrypted_credentials);

            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if credentials are expired (for OAuth tokens)
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get active credential for a provider
     */
    public static function getActiveCredential(string $provider, string $type = 'api_key'): ?self
    {
        return static::where('provider', $provider)
            ->where('credential_type', $type)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Store or update credentials for a provider
     */
    public static function storeCredentials(
        string $provider,
        array $credentials,
        string $type = 'api_key',
        array $metadata = [],
        ?\DateTime $expiresAt = null
    ): self {
        // Encrypt the credentials first
        $encryptedCredentials = Crypt::encrypt(json_encode($credentials));

        $credential = static::updateOrCreate(
            [
                'provider' => $provider,
                'credential_type' => $type,
            ],
            [
                'encrypted_credentials' => $encryptedCredentials,
                'metadata' => $metadata,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );

        return $credential;
    }

    /**
     * Relationship: Credential belongs to provider config
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider', 'provider');
    }

    /**
     * Get or ensure provider config exists
     */
    public function getProvider(): Provider
    {
        if ($this->relationLoaded('provider')) {
            return $this->provider;
        }

        // Create or get provider
        return Provider::getOrCreateForProvider($this->provider);
    }

    /**
     * Check if provider is enabled
     */
    public function isProviderEnabled(): bool
    {
        $config = $this->getProvider();

        return $config->enabled;
    }

    /**
     * Get UI metadata with defaults
     */
    public function getUIMetadata(): array
    {
        return array_merge([
            'name' => null,
            'description' => null,
            'tags' => [],
            'last_tested' => null,
            'test_results' => null,
            'configuration_hints' => [],
        ], $this->ui_metadata ?? []);
    }

    /**
     * Update usage statistics
     */
    public function updateUsageStats(float $cost = 0): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        if ($cost > 0) {
            $this->increment('total_cost', $cost);
        }

        // Also update provider config stats
        $config = $this->getProvider();
        $config->incrementUsage($cost);
    }

    /**
     * Test credential validity
     */
    public function testCredentials(): array
    {
        // This would integrate with the provider's health check
        // For now, return basic validation
        $credentials = $this->getCredentials();

        $result = [
            'valid' => ! empty($credentials),
            'tested_at' => now()->toISOString(),
            'details' => [],
        ];

        // Store test results in UI metadata
        $uiMetadata = $this->getUIMetadata();
        $uiMetadata['last_tested'] = $result['tested_at'];
        $uiMetadata['test_results'] = $result;

        $this->update(['ui_metadata' => $uiMetadata]);

        return $result;
    }

    /**
     * Enhanced store method that creates provider config
     */
    public static function storeCredentialsEnhanced(
        string $provider,
        array $credentials,
        string $type = 'api_key',
        array $metadata = [],
        array $uiMetadata = [],
        ?\DateTime $expiresAt = null
    ): self {
        // Ensure provider config exists
        $providerConfig = Provider::getOrCreateForProvider($provider);

        // Encrypt the credentials first
        $encryptedCredentials = Crypt::encrypt(json_encode($credentials));

        $credential = static::updateOrCreate(
            [
                'provider' => $provider,
                'credential_type' => $type,
            ],
            [
                'encrypted_credentials' => $encryptedCredentials,
                'metadata' => $metadata,
                'ui_metadata' => $uiMetadata,
                'provider_config_id' => $providerConfig->id,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );

        return $credential;
    }

    /**
     * Get active credential with provider enabled check
     */
    public static function getActiveEnabledCredential(string $provider, string $type = 'api_key'): ?self
    {
        return static::where('provider', $provider)
            ->where('credential_type', $type)
            ->where('is_active', true)
            ->whereHas('providerConfig', function ($query) {
                $query->where('enabled', true);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
