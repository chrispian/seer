<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiComponent extends Model
{
    protected $fillable = [
        'key',
        'type',
        'config',
        'hash',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FeUiComponent $component) {
            $component->hash = hash('sha256', json_encode($component->config));

            if ($component->exists && $component->isDirty('config')) {
                $component->version++;
            }
        });
    }
}
