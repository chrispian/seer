<?php

namespace Modules\UiBuilder\app\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    protected $table = 'ui_components';

    protected $fillable = [
        'key',
        'type',
        'kind',
        'config',
        'variant',
        'schema_json',
        'defaults_json',
        'capabilities_json',
        'hash',
        'version',
    ];

    protected $casts = [
        'config' => 'array',
        'schema_json' => 'array',
        'defaults_json' => 'array',
        'capabilities_json' => 'array',
        'version' => 'integer',
    ];

    public function scopeByKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
