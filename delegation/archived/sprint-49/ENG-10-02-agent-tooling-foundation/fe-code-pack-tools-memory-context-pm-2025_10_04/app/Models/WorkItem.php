<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkItem extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'work_items';

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'state' => 'array',
        'metadata' => 'array',
    ];
}
