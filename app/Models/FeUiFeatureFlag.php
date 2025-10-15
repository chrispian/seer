<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeUiFeatureFlag extends Model
{
    use SoftDeletes;

    protected $table = 'fe_ui_feature_flags';

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
        'percentage',
        'conditions',
        'metadata',
        'environment',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'percentage' => 'integer',
        'conditions' => 'array',
        'metadata' => 'array',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForEnvironment($query, ?string $environment = null)
    {
        $env = $environment ?? app()->environment();
        return $query->where(function ($q) use ($env) {
            $q->whereNull('environment')
              ->orWhere('environment', $env);
        });
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
