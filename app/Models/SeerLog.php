<?php

namespace App\Models;

class SeerLog extends Fragment
{
    protected $table = 'fragments';

    protected static function booted(): void
    {
        static::creating(function (SeerLog $log) {
            if (empty($log->type)) {
                $log->type = 'obs';
            }

            $log->tags = is_array($log->tags) ? $log->tags : ($log->tags ? (array) $log->tags : []);
            $log->relationships = is_array($log->relationships) ? $log->relationships : ($log->relationships ? (array) $log->relationships : []);
        });
    }
}
