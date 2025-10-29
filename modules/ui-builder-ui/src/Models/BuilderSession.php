<?php

namespace HollisLabs\UiBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class BuilderSession extends Model
{
    protected $table = 'fe_ui_builder_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'page_key',
        'title',
        'overlay',
        'route',
        'module_key',
        'layout_type',
        'layout_id',
        'state_json',
        'config_json',
        'expires_at',
    ];

    protected $casts = [
        'state_json' => 'array',
        'config_json' => 'array',
        'expires_at' => 'datetime',
    ];

    public function components()
    {
        return $this->hasMany(BuilderPageComponent::class, 'session_id', 'session_id');
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeBySessionId($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function generateConfig(): array
    {
        $components = $this->components()
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(fn($c) => $c->toComponentConfig())
            ->toArray();

        return [
            'id' => $this->page_key,
            'overlay' => $this->overlay ?? 'page',
            'title' => $this->title,
            'layout' => [
                'type' => $this->layout_type ?? 'rows',
                'id' => $this->layout_id ?? 'root-layout',
                'children' => $components,
            ],
        ];
    }
}
