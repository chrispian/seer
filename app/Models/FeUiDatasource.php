<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiDatasource extends Model
{
    protected $fillable = [
        'alias',
        'model_class',
        'resolver_class',
        'capabilities',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
        ];
    }
}
