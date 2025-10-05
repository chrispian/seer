<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AgentNote extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'agent_notes';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'links' => 'array',
        'tags' => 'array',
        'provenance' => 'array',
        'ttl_at' => 'datetime',
    ];
}
