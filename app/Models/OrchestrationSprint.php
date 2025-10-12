<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrchestrationSprint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sprint_code',
        'title',
        'status',
        'owner',
        'hash',
        'metadata',
        'file_path',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(OrchestrationTask::class, 'sprint_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrchestrationEvent::class, 'entity_id')
            ->where('entity_type', 'sprint');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function generateHash(): string
    {
        return hash('sha256', 
            $this->sprint_code . 
            json_encode($this->metadata ?? []) . 
            $this->updated_at->timestamp
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($sprint) {
            if ($sprint->isDirty(['sprint_code', 'metadata'])) {
                $sprint->hash = $sprint->generateHash();
            }
        });
    }
}
