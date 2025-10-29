<?php

namespace HollisLabs\UiBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class BuilderPageComponent extends Model
{
    protected $table = 'fe_ui_builder_page_components';

    protected $fillable = [
        'session_id',
        'component_id',
        'component_type',
        'parent_id',
        'order',
        'props_json',
        'actions_json',
        'children_json',
    ];

    protected $casts = [
        'props_json' => 'array',
        'actions_json' => 'array',
        'children_json' => 'array',
        'order' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(BuilderSession::class, 'session_id', 'session_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function toComponentConfig(): array
    {
        $config = [
            'id' => $this->component_id,
            'type' => $this->component_type,
        ];

        if ($this->props_json) {
            $config['props'] = $this->props_json;
        }

        if ($this->actions_json) {
            $config['actions'] = $this->actions_json;
        }

        // Recursively add children
        $children = $this->children()->orderBy('order')->get();
        if ($children->isNotEmpty()) {
            $config['children'] = $children->map(fn($c) => $c->toComponentConfig())->toArray();
        }

        return $config;
    }
}
