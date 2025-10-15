<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeTypeRelation extends Model
{
    protected $table = 'fe_type_relations';
    protected $guarded = [];
    protected $casts = [
        'options_json' => 'array',
    ];
}
