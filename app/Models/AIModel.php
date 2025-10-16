<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiModel represents an AI model configuration in the system.
 * 
 * This model stores information about available AI models from various providers
 * (like OpenAI, Anthropic, etc.) including their capabilities, pricing, limits,
 * and availability status. Each AI model belongs to an AiProvider.
 * 
 * Examples: gpt-4, claude-3-opus, llama-2-70b
 * 
 * @property int $id
 * @property int $provider_id Foreign key to AiProvider
 * @property string $model_id Unique identifier from the provider (e.g., 'gpt-4-turbo')
 * @property string $name Display name for the model
 * @property string|null $description Model description and capabilities
 * @property array $capabilities Model capabilities (chat, completion, embeddings, etc.)
 * @property array $pricing Pricing information per token/request
 * @property array $limits Rate limits and constraints
 * @property string|null $logo_url URL to model/provider logo
 * @property bool $enabled Whether this model is enabled for use
 * @property int $priority Display/selection priority (lower = higher priority)
 * @property array|null $metadata Additional model-specific metadata
 * @property \Carbon\Carbon|null $synced_at Last sync timestamp with provider
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiModel extends Model
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
        return $this->belongsTo(AiProvider::class, 'provider_id');
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
        return $this->provider->provider.'/'.$this->model_id;
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
