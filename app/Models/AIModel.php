<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIModel extends Model
{
    protected $table = 'models';

    protected $fillable = [
        'provider_id',
        'model_id',
        'name',
        'description',
        'capabilities',
        'pricing',
        'limits',
        'logo_url',
        'enabled',
        'priority',
        'metadata',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'pricing' => 'array',
            'limits' => 'array',
            'enabled' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Relationship: Model belongs to a provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Check if model is available (enabled and provider is enabled)
     */
    public function isAvailable(): bool
    {
        return $this->enabled && $this->provider->enabled;
    }

    /**
     * Get display name for UI
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Get full model identifier (provider/model)
     */
    public function getFullIdentifier(): string
    {
        return $this->provider->provider . '/' . $this->model_id;
    }

    /**
     * Check if model supports a specific capability
     */
    public function supportsCapability(string $capability): bool
    {
        $capabilities = $this->capabilities ?? [];
        return in_array($capability, $capabilities);
    }

    /**
     * Get enabled models ordered by priority
     */
    public static function getEnabledModels()
    {
        return static::where('enabled', true)
            ->whereHas('provider', function ($query) {
                $query->where('enabled', true);
            })
            ->with('provider')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get models by capability
     */
    public static function getModelsByCapability(string $capability)
    {
        return static::where('enabled', true)
            ->whereHas('provider', function ($query) {
                $query->where('enabled', true);
            })
            ->whereJsonContains('capabilities', $capability)
            ->with('provider')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get models for a specific provider
     */
    public static function getModelsForProvider(Provider $provider)
    {
        return static::where('provider_id', $provider->id)
            ->where('enabled', true)
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
    }
}