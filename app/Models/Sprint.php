<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'sprints';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get the sprint items
     */
    public function sprintItems(): HasMany
    {
        return $this->hasMany(SprintItem::class);
    }

    /**
     * Get the work items in this sprint
     */
    public function workItems()
    {
        return $this->hasManyThrough(
            WorkItem::class,
            SprintItem::class,
            'sprint_id',
            'id',
            'id',
            'work_item_id'
        );
    }
}
