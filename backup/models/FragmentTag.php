<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FragmentTag extends Model
{
    protected $table = 'fragment_tags';

    public $timestamps = false;

    public function fragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class);
    }
}
