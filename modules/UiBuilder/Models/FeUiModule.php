<?php

namespace Modules\UiBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiModule extends Model
{
    protected $table = 'fe_ui_modules';

    protected $fillable = [
        'key',
        'title',
        'description',
        'manifest_json',
        'version',
        'hash',
        'enabled',
        'order',
        'capabilities',
        'permissions',
    ];

    protected $casts = [
        'manifest_json' => 'array',
        'capabilities' => 'array',
        'permissions' => 'array',
        'enabled' => 'boolean',
        'order' => 'integer',
    ];

    public function pages()
    {
        return $this->hasMany(FeUiPage::class, 'module_key', 'key');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($module) {
            if (!$module->hash || $module->isDirty(['key', 'version'])) {
                $module->hash = hash('sha256', $module->key . '.' . $module->version);
            }
        });
    }
}
