<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandRegistry extends Model
{
    protected $table = 'command_registry';

    protected $fillable = [
        'slug',
        'version',
        'source_path',
        'steps_hash',
        'capabilities',
        'requires_secrets',
        'reserved',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'requires_secrets' => 'array',
        'reserved' => 'boolean',
    ];

    /**
     * Get the registry entry for a specific command slug
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
     * Check if steps have changed based on hash
     */
    public function hasStepsChanged(string $newHash): bool
    {
        return $this->steps_hash !== $newHash;
    }

    /**
     * Get command capabilities
     */
    public function getCapabilities(): array
    {
        return $this->capabilities ?? [];
    }

    /**
     * Get required secrets
     */
    public function getRequiredSecrets(): array
    {
        return $this->requires_secrets ?? [];
    }

    /**
     * Check if command is reserved (built-in)
     */
    public function isReserved(): bool
    {
        return $this->reserved;
    }
}
