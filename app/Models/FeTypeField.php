<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeTypeField extends Model
{
    protected $fillable = [
        'fe_type_id',
        'name',
        'type',
        'label',
        'required',
        'searchable',
        'sortable',
        'filterable',
        'validation',
        'metadata',
        'order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'searchable' => 'boolean',
        'sortable' => 'boolean',
        'filterable' => 'boolean',
        'validation' => 'array',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    public function feType(): BelongsTo
    {
        return $this->belongsTo(FeType::class);
    }

    public function scopeSearchable($query)
    {
        return $query->where('searchable', true);
    }

    public function scopeSortable($query)
    {
        return $query->where('sortable', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('filterable', true);
    }
}
