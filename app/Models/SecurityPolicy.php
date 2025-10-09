<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SecurityPolicy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(fn () => self::clearPolicyCache());
        static::updated(fn () => self::clearPolicyCache());
        static::deleted(fn () => self::clearPolicyCache());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('policy_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAllow($query)
    {
        return $query->where('action', 'allow');
    }

    public function scopeDeny($query)
    {
        return $query->where('action', 'deny');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('id');
    }

    public static function clearPolicyCache(): void
    {
        // Clear main cache keys
        Cache::forget('security:policies:all');
        Cache::forget('security:policies:by_type');
        Cache::forget('security:policies:by_category');
        
        // Clear all type-specific cache keys (e.g., security:policies:type:command, security:policies:type:path, etc.)
        $types = ['command', 'path', 'tool', 'domain'];
        foreach ($types as $type) {
            Cache::forget("security:policies:type:{$type}");
        }
    }

    public function getRiskWeight(): int
    {
        return $this->metadata['risk_weight'] ?? 0;
    }

    public function getTimeout(): ?int
    {
        return $this->metadata['timeout'] ?? null;
    }
}
