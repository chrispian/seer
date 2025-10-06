<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AgentVector extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'agent_vectors';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'embedding' => 'array',
        'meta' => 'array',
    ];
}
