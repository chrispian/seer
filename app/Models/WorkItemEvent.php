<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkItemEvent extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'work_item_events';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = [
        'meta' => 'array',
    ];
}
