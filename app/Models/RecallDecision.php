<?php

namespace App\Models;

use App\Database\JsonCastingBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

class RecallDecision extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'parsed_query',
        'total_results',
        'selected_fragment_id',
        'selected_index',
        'action',
        'context',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_query' => 'array',
            'context' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function selectedFragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'selected_fragment_id');
    }

    public function newEloquentBuilder($query): JsonCastingBuilder
    {
        /** @var QueryBuilder $query */
        return new JsonCastingBuilder($query);
    }
}
