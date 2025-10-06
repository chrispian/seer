<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromptEntry extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'prompt_registry';

    protected $guarded = [];

    protected $casts = [
        'variables' => 'array',
        'tags' => 'array',
    ];
}
