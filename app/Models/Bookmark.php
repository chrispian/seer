<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $guarded = [];
    protected $casts = [
        'fragment_ids' => 'array',
    ];
}
