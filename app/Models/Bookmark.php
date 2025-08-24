<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'name',
        'fragment_ids',
    ];
    
    protected $casts = [
        'fragment_ids' => 'array',
    ];
}
