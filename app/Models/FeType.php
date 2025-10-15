<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeType extends Model
{
    protected $fillable = [
        'alias',
        'source_type',
        'config',
        'capabilities',
        'metadata',
        'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'enabled' => 'boolean',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FeTypeField::class)->orderBy('order');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(FeTypeRelation::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByAlias($query, string $alias)
    {
        return $query->where('alias', $alias);
    }
}
