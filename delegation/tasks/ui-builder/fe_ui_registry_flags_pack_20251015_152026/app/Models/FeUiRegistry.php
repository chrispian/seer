<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiRegistry extends Model
{
    protected $table = 'fe_ui_registry';
    protected $guarded = [];
    protected $casts = [
        'manifest_json' => 'array',
        'tags_json' => 'array',
        'enabled' => 'boolean',
    ];
}
