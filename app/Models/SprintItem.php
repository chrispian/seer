<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SprintItem extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'sprint_items';
    protected $guarded = [];
}
