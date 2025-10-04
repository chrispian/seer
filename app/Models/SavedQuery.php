<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SavedQuery extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'saved_queries';
    protected $guarded = [];
    protected $casts = [
        'filters' => 'array',
        'boosts' => 'array',
        'order_by' => 'array',
    ];
}
