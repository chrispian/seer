<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Type Model
 *
 * Represents a unified type in the system - can be model-backed or fragment-backed.
 * Types define data structures and their default UI presentation.
 */
class Type extends Model
{
    protected $table = 'types_registry';

    protected $fillable = [
        'slug',
        'display_name',
        'plural_name',
        'description',
        'icon',
        'color',
        'storage_type',
        'model_class',
        'schema',
        'default_card_component',
        'default_detail_component',
        'capabilities',
        'hot_fields',
        'is_enabled',
        'is_system',
    ];

    protected $casts = [
        'schema' => 'array',
        'capabilities' => 'array',
        'hot_fields' => 'array',
        'is_enabled' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the commands associated with this type
     */
    public function commands(): HasMany
    {
        return $this->hasMany(Command::class, 'type_slug', 'slug');
    }

    /**
     * Get fragments for fragment-backed types
     */
    public function fragments(): HasMany
    {
        return $this->hasMany(Fragment::class, 'type', 'slug');
    }

    /**
     * Scope: Only enabled types
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Model-backed types (have their own tables)
     */
    public function scopeModelBacked($query)
    {
        return $query->where('storage_type', 'model');
    }

    /**
     * Scope: Fragment-backed types (stored as fragments with schema)
     */
    public function scopeFragmentBacked($query)
    {
        return $query->where('storage_type', 'fragment');
    }

    /**
     * Get the type by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Check if this is a model-backed type
     */
    public function isModelBacked(): bool
    {
        return $this->storage_type === 'model';
    }

    /**
     * Check if this is a fragment-backed type
     */
    public function isFragmentBacked(): bool
    {
        return $this->storage_type === 'fragment';
    }

    /**
     * Check if type can be disabled
     */
    public function canBeDisabled(): bool
    {
        return !$this->is_system;
    }

    /**
     * Check if type can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system && $this->fragments()->count() === 0;
    }

    /**
     * Get the model class instance for model-backed types
     */
    public function getModelInstance(): ?Model
    {
        if (!$this->isModelBacked() || !$this->model_class) {
            return null;
        }

        if (!class_exists($this->model_class)) {
            return null;
        }

        return new $this->model_class;
    }
}
