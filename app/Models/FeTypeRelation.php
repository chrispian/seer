<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeTypeRelation extends Model
{
    protected $fillable = [
        'fe_type_id',
        'name',
        'type',
        'related_type',
        'foreign_key',
        'local_key',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function feType(): BelongsTo
    {
        return $this->belongsTo(FeType::class);
    }
}
