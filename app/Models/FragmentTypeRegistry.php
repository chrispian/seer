<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FragmentTypeRegistry extends Model
{
    protected $table = 'fragment_type_registry';

    protected $fillable = [
        'slug',
        'version',
        'source_path',
        'schema_hash',
        'hot_fields',
        'capabilities',
    ];

    protected $casts = [
        'hot_fields' => 'array',
        'capabilities' => 'array',
    ];

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
}
