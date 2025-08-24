<?php

namespace App\Models;

use App\Enums\RelationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FragmentLink extends Model
{
    protected $table = 'fragment_links';
    
    protected $fillable = [
        'from_id',
        'to_id',
        'relation',
    ];

    protected $casts = [
        'relation' => RelationType::class,
    ];

    public function fromFragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'from_id');
    }

    public function toFragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'to_id');
    }
}
