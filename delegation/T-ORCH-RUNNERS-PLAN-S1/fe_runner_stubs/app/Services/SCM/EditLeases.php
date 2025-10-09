<?php

declare(strict_types=1);

namespace App\Services\SCM;

use Illuminate\Support\Facades\Redis;

class EditLeases
{
    public function acquire(string $path, int $ttl = 1200): bool
    {
        $key = "lease:file:{$path}";
        $ok = Redis::setnx($key, getmypid());
        if ($ok) Redis::expire($key, $ttl);
        return $ok;
    }
}
