<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiDatasource extends Model
{
    protected $table = 'fe_ui_datasources';

    protected $fillable = [
        'alias',
        'model_class',
        'handler',
        'resolver_class',
        'capabilities',
        'default_params_json',
        'capabilities_json',
        'schema_json',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'default_params_json' => 'array',
        'capabilities_json' => 'array',
        'schema_json' => 'array',
    ];

    public function scopeByAlias($query, string $alias)
    {
        return $query->where('alias', $alias);
    }
}
