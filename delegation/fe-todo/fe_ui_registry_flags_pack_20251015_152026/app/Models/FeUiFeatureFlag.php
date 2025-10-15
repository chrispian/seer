<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiFeatureFlag extends Model
{
    protected $table = 'fe_ui_feature_flags';
    protected $guarded = [];
    protected $casts = [
        'conditions_json' => 'array',
        'enabled' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
