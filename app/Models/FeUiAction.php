<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiAction extends Model
{
    protected $fillable = [
        'type',
        'handler_class',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
