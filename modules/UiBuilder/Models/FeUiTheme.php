<?php

namespace Modules\UiBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiTheme extends Model
{
    protected $table = 'fe_ui_themes';

    protected $fillable = [
        'key',
        'title',
        'description',
        'design_tokens_json',
        'tailwind_overrides_json',
        'variants_json',
        'version',
        'hash',
        'enabled',
        'is_default',
    ];

    protected $casts = [
        'design_tokens_json' => 'array',
        'tailwind_overrides_json' => 'array',
        'variants_json' => 'array',
        'enabled' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($theme) {
            if (!$theme->hash || $theme->isDirty(['key', 'version'])) {
                $theme->hash = hash('sha256', $theme->key . '.' . $theme->version);
            }
        });
    }
}
