<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'designation',
        'avatar_path',
        'agent_profile_id',
        'persona',
        'tool_config',
        'metadata',
        'version',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tool_config' => 'array',
            'metadata' => 'array',
            'version' => 'integer',
        ];
    }

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    protected function avatarUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->avatar_path 
                ? asset('storage/' . $this->avatar_path) 
                : asset('/interface/avatars/default/avatar-1.png')
        );
    }
}
