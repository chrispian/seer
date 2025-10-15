<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiPage extends Model
{
    protected $table = 'fe_ui_pages';

    protected $fillable = [
        'key',
        'layout_tree_json',
        'route',
        'meta_json',
        'module_key',
        'guards_json',
        'enabled',
        'hash',
        'version',
    ];

    protected $casts = [
        'layout_tree_json' => 'array',
        'meta_json' => 'array',
        'guards_json' => 'array',
        'enabled' => 'boolean',
        'version' => 'integer',
    ];

    public function module()
    {
        return $this->belongsTo(FeUiModule::class, 'module_key', 'key');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByRoute($query, string $route)
    {
        return $query->where('route', $route);
    }
}
