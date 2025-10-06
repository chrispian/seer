<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AgentDecision extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'agent_decisions';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'alternatives' => 'array',
        'links' => 'array',
        'confidence' => 'float',
    ];
}
