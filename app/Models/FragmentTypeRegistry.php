<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fragment Type Registry
 *
 * Central registry for all fragment types. Stores configuration,
 * UI settings, and management flags. Source of truth for type system.
 *
 * No YAML - pure DB + PHP classes approach.
 */
class FragmentTypeRegistry extends Model
{
    protected $table = 'fragment_type_registry';

    protected $fillable = [
        'slug',
        'display_name',
        'plural_name',
        'description',
        'icon',
        'color',
        'version',
        'source_path',
        'schema_hash',
        'hot_fields',
        'capabilities',
        'is_enabled',
        'is_system',
        'hide_from_admin',
        'list_columns',
        'filters',
        'actions',
        'default_sort',
        'pagination_default',
        'config_class',
        'behaviors',
        'container_component',
        'row_display_mode',
        'detail_component',
        'detail_fields',
    ];

    protected $casts = [
        'hot_fields' => 'array',
        'capabilities' => 'array',
        'is_enabled' => 'boolean',
        'is_system' => 'boolean',
        'hide_from_admin' => 'boolean',
        'list_columns' => 'array',
        'filters' => 'array',
        'actions' => 'array',
        'default_sort' => 'array',
        'behaviors' => 'array',
        'detail_fields' => 'array',
    ];

    /**
     * Scope: Only enabled types
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: User-manageable types (exclude system types hidden from admin)
     */
    public function scopeUserManageable($query)
    {
        return $query->where('hide_from_admin', false);
    }

    /**
     * Get the registry entry for a specific type slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Update or create registry entry
     */
    public static function updateOrCreateEntry(string $slug, array $data): self
    {
        return static::updateOrCreate(
            ['slug' => $slug],
            $data
        );
    }

    /**
     * Check if type can be disabled
     */
    public function canBeDisabled(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Check if type can be deleted
     */
    public function canBeDeleted(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Check if schema has changed based on hash
     */
    public function hasSchemaChanged(string $newHash): bool
    {
        return $this->schema_hash !== $newHash;
    }

    /**
     * Get hot fields for query optimization
     */
    public function getHotFields(): array
    {
        return $this->hot_fields ?? [];
    }

    /**
     * Get type capabilities
     */
    public function getCapabilities(): array
    {
        return $this->capabilities ?? [];
    }

    /**
     * Get PHP config class instance (if exists)
     */
    public function getConfigInstance(): ?object
    {
        if (! $this->config_class || ! class_exists($this->config_class)) {
            return null;
        }

        return app($this->config_class);
    }
}
