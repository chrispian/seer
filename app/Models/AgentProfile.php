<?php

namespace App\Models;

use App\Enums\AgentMode;
use App\Enums\AgentStatus;
use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class AgentProfile extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'mode',
        'description',
        'capabilities',
        'constraints',
        'tools',
        'metadata',
        'status',
    ];

    protected $casts = [
        'type' => AgentType::class,
        'mode' => AgentMode::class,
        'status' => AgentStatus::class,
        'capabilities' => 'array',
        'constraints' => 'array',
        'tools' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AgentProfile $profile): void {
            if (empty($profile->slug)) {
                $profile->slug = static::generateUniqueSlug($profile->name);
            }

            if (empty($profile->mode) && !empty($profile->type)) {
                $profile->mode = static::resolveType($profile->type)?->defaultMode();
            }

            if (empty($profile->status)) {
                $profile->status = AgentStatus::Active;
            }
        });

        static::updating(function (AgentProfile $profile): void {
            if (empty($profile->slug)) {
                $profile->slug = static::generateUniqueSlug($profile->name, $profile->getKey());
            }

            if (empty($profile->mode) && $profile->isDirty('type')) {
                $profile->mode = static::resolveType($profile->type)?->defaultMode();
            }
        });
    }

    /**
     * Get all task assignments for this agent
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'agent_id');
    }

    /**
     * Get active task assignments
     */
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereIn('status', ['assigned', 'started']);
    }

    /**
     * Get assigned work items through task assignments
     */
    public function assignedTasks(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkItem::class,
            TaskAssignment::class,
            'agent_id',
            'id',
            'id',
            'work_item_id'
        );
    }

    /**
     * Scope for active agents
     */
    public function scopeActive($query)
    {
        return $query->where('status', AgentStatus::Active->value);
    }

    /**
     * Scope by agent type
     */
    public function scopeByType($query, AgentType|string $type)
    {
        $value = $type instanceof AgentType ? $type->value : $type;

        return $query->where('type', $value);
    }

    /**
     * Scope by agent mode
     */
    public function scopeByMode($query, AgentMode|string $mode)
    {
        $value = $mode instanceof AgentMode ? $mode->value : $mode;

        return $query->where('mode', $value);
    }

    /**
     * Scope by agent status
     */
    public function scopeByStatus($query, AgentStatus|string $status)
    {
        $value = $status instanceof AgentStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * Scope for simple text search by name or slug
     */
    public function scopeSearch($query, string $term)
    {
        $like = '%' . $term . '%';

        return $query->where(function ($inner) use ($like) {
            $inner->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like);
        });
    }

    private static function generateUniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(12);
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private static function resolveType(null|AgentType|string $type): ?AgentType
    {
        if ($type instanceof AgentType) {
            return $type;
        }

        if (is_string($type)) {
            return AgentType::tryFrom($type) ?? AgentType::fromLabel($type);
        }

        return null;
    }
}
