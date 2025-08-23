<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RouteFragment
{
    public function __invoke(string $input): Fragment
    {
        Log::debug('RouteFragment::invoke()');
        $fragment = Fragment::create([
            'vault' => 'default',
            'type' => 'log',
            'message' => $input,
            'source' => 'chat',
        ]);

        return $fragment;
    }
}
