<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vault extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_default',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('sort_order')->orderBy('name');
    }

    public function fragments(): HasMany
    {
        return $this->hasMany(Fragment::class, 'vault');
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault(): ?self
    {
        return static::default()->first() ?? static::first();
    }
}
