<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AiProvider represents an AI service provider in the system.
 * 
 * This model stores configuration for AI providers (companies/services that offer AI models)
 * such as OpenAI, Anthropic, Google, etc. Each provider can have multiple AI models
 * and maintains its own rate limits, health status, and usage tracking.
 * 
 * Examples: OpenAI, Anthropic, Google AI, Hugging Face, Replicate
 * 
 * @property int $id
 * @property string $provider Unique provider identifier (e.g., 'openai', 'anthropic')
 * @property string $name Display name (e.g., 'OpenAI', 'Anthropic')
 * @property string|null $description Provider description
 * @property string|null $logo_url URL to provider logo
 * @property bool $enabled Whether this provider is enabled
 * @property array|null $ui_preferences UI display preferences
 * @property array|null $capabilities Provider capabilities
 * @property array|null $rate_limits Rate limiting configuration
 * @property int $usage_count Total API calls to this provider
 * @property float $total_cost Total cost incurred from this provider
 * @property \Carbon\Carbon|null $last_health_check Last health check timestamp
 * @property array|null $health_status Health check results
 * @property int $priority Display/selection priority (lower = higher priority)
 * @property array|null $metadata Additional provider-specific metadata
 * @property \Carbon\Carbon|null $synced_at Last sync timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiProvider extends Model
{
    protected $table = 'providers';

    protected $fillable = [
        'provider',
        'name',
        'description',
        'logo_url',
        'enabled',
        'ui_preferences',
        'capabilities',
        'rate_limits',
        'usage_count',
        'total_cost',
        'last_health_check',
        'health_status',
        'priority',
        'metadata',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'ui_preferences' => 'array',
            'capabilities' => 'array',
            'rate_limits' => 'array',
            'usage_count' => 'integer',
            'total_cost' => 'decimal:6',
            'last_health_check' => 'datetime',
            'health_status' => 'array',
            'priority' => 'integer',
            'metadata' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Relationship: Provider has many models
     */
    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }

    /**
     * Relationship: Provider has many credentials
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(AiCredential::class, 'provider', 'provider');
    }

    /**
     * Relationship: Get active credentials only
     */
    public function activeCredentials(): HasMany
    {
        return $this->credentials()->where('is_active', true);
    }

    /**
     * Check if provider is available (enabled and has valid credentials)
     */
    public function isAvailable(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        return $this->activeCredentials()->exists();
    }

    /**
     * Get provider health status
     */
    public function getHealthStatus(): string
    {
        if (! $this->enabled) {
            return 'disabled';
        }

        if (! $this->last_health_check) {
            return 'unknown';
        }

        $status = $this->health_status['status'] ?? 'unknown';
        $lastCheck = $this->last_health_check;

        // Consider status stale if last check was over 30 minutes ago
        if ($lastCheck->diffInMinutes(now()) > 30) {
            return 'stale';
        }

        return $status;
    }

    /**
     * Update health check status
     */
    public function updateHealthStatus(bool $healthy, array $details = []): void
    {
        $this->update([
            'last_health_check' => now(),
            'health_status' => [
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'details' => $details,
                'checked_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Increment usage statistics
     */
    public function incrementUsage(float $cost = 0): void
    {
        $this->increment('usage_count');

        if ($cost > 0) {
            $this->increment('total_cost', $cost);
        }
    }

    /**
     * Get UI preferences with defaults
     */
    public function getUIPreferences(): array
    {
        return array_merge([
            'color' => null,
            'icon' => null,
            'display_name' => $this->getDisplayName(),
            'description' => null,
            'tags' => [],
            'featured' => false,
            'hidden' => false,
        ], $this->ui_preferences ?? []);
    }

    /**
     * Get display name for UI
     */
    public function getDisplayName(): string
    {
        $preferences = $this->ui_preferences ?? [];

        if (isset($preferences['display_name'])) {
            return $preferences['display_name'];
        }

        // Use the database name field, fallback to provider field
        return $this->name ?? ucfirst($this->provider ?? 'Unknown');
    }

    /**
     * Get capabilities from database
     */
    public function getCapabilities(): array
    {
        return $this->capabilities ?? [];
    }

    /**
     * Update sync timestamp for models.dev synchronization
     */
    public function markAsSynced(): void
    {
        $this->update([
            'synced_at' => now(),
        ]);
    }

    /**
     * Get or create provider config
     */
    public static function getOrCreateForProvider(string $provider): self
    {
        $config = static::where('provider', $provider)->first();

        if (! $config) {
            $config = static::create([
                'provider' => $provider,
                'enabled' => true,
                'priority' => 50,
            ]);

            // Sync capabilities from config
            $config->syncFromConfig();
        }

        return $config;
    }

    /**
     * Get all enabled providers ordered by priority
     */
    public static function getEnabledProviders()
    {
        return static::where('enabled', true)
            ->orderBy('priority', 'desc')
            ->orderBy('provider')
            ->get();
    }

    /**
     * Get provider statistics for dashboard
     */
    public static function getProviderStats(): array
    {
        $configs = static::all();

        return [
            'total_providers' => $configs->count(),
            'enabled_providers' => $configs->where('enabled', true)->count(),
            'healthy_providers' => $configs->filter(fn ($c) => $c->getHealthStatus() === 'healthy')->count(),
            'total_usage' => $configs->sum('usage_count'),
            'total_cost' => $configs->sum('total_cost'),
            'last_health_check' => $configs->max('last_health_check'),
        ];
    }
}
