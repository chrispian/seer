<?php

namespace Modules\UiBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiAction extends Model
{
    protected $table = 'fe_ui_actions';

    protected $fillable = [
        'type',
        'handler_class',
        'config',
        'payload_schema_json',
        'policy_json',
    ];

    protected $casts = [
        'config' => 'array',
        'payload_schema_json' => 'array',
        'policy_json' => 'array',
    ];

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
