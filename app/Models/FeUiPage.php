<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeUiPage extends Model
{
    protected $fillable = [
        'key',
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
        static::saving(function (FeUiPage $page) {
            $page->hash = hash('sha256', json_encode($page->config));

            if ($page->exists && $page->isDirty('config')) {
                $page->version++;
            }
        });
    }
}
