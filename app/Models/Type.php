<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    /** @use HasFactory<\Database\Factories\TypeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function fragments(): HasMany
    {
        return $this->hasMany(Fragment::class);
    }

    public static function findByValue(string $value): ?self
    {
        return static::where('value', $value)->first();
    }
}
