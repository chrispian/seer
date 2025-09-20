<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    protected $casts = [
        'status' => ArticleStatus::class,
        'meta' => 'array',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(ArticleFragment::class)->orderBy('order_pos');
    }

    public function build(): HasOne
    {
        return $this->hasOne(Build::class);
    }
}
