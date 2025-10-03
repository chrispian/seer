<?php

namespace App\Listeners\Projectors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\Fragments\FragmentCreated;
use App\Events\Fragments\FragmentUpdated;
use App\Events\Fragments\FragmentDeleted;

class FragmentLifecycleProjector
{
    public function onCreated(FragmentCreated $e): void
    {
        DB::table('fragment_activity')->insert([
            'id' => (string) Str::uuid(),
            'fragment_id' => $e->fragmentId,
            'action' => 'created',
            'by_user' => $e->userId,
            'payload' => json_encode(['type'=>$e->type]),
            'ts' => now(),
        ]);
    }

    public function onUpdated(FragmentUpdated $e): void
    {
        DB::table('fragment_activity')->insert([
            'id' => (string) Str::uuid(),
            'fragment_id' => $e->fragmentId,
            'action' => 'updated',
            'by_user' => $e->userId,
            'payload' => json_encode($e->diff),
            'ts' => now(),
        ]);
    }

    public function onDeleted(FragmentDeleted $e): void
    {
        DB::table('fragment_activity')->insert([
            'id' => (string) Str::uuid(),
            'fragment_id' => $e->fragmentId,
            'action' => 'deleted',
            'by_user' => $e->userId,
            'payload' => json_encode(null),
            'ts' => now(),
        ]);
    }
}
