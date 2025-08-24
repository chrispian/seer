<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    protected $table = 'todos';
    
    protected $primaryKey = 'fragment_id';
    
    public $incrementing = false;
    
    public $timestamps = false;
    

    protected $casts = [
        'state' => 'array',
    ];

    public function fragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'fragment_id');
    }
}
