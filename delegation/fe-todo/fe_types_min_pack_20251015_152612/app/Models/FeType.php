<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeType extends Model
{
    protected $table = 'fe_types';
    protected $guarded = [];
    protected $casts = [
        'meta_json' => 'array',
        'options_json' => 'array',
    ];

    public function fields()
    {
        return $this->hasMany(FeTypeField::class)->orderBy('order');
    }

    public function relations()
    {
        return $this->hasMany(FeTypeRelation::class)->orderBy('order');
    }
}
