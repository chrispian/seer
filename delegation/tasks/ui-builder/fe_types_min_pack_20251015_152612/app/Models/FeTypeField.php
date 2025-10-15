<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeTypeField extends Model
{
    protected $table = 'fe_type_fields';
    protected $guarded = [];
    protected $casts = [
        'options_json' => 'array',
        'required' => 'boolean',
        'unique' => 'boolean',
    ];
}
