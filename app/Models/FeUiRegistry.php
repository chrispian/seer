<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeUiRegistry extends Model
{
    use SoftDeletes;

    protected $table = 'fe_ui_registry';

    protected $fillable = [
        'type',
        'name',
        'slug',
        'description',
        'version',
        'reference_type',
        'reference_id',
        'metadata',
        'hash',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function generateHash(): string
    {
        $timestamp = $this->updated_at ? $this->updated_at->timestamp : now()->timestamp;
        
        return hash('sha256', 
            $this->slug . 
            $this->version . 
            json_encode($this->metadata ?? []) . 
            $timestamp
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($registry) {
            if (!$registry->updated_at) {
                $registry->updated_at = now();
            }
            
            if (!$registry->exists || $registry->isDirty(['slug', 'version', 'metadata'])) {
                $registry->hash = $registry->generateHash();
            }
        });
    }
}
