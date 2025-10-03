<?php

namespace App\Listeners\Projectors;

use App\Events\Fragments\FragmentCreated;
use App\Events\Fragments\FragmentUpdated;
use App\Events\Fragments\FragmentDeleted;
use Illuminate\Support\Facades\DB;

class FragmentLifecycleProjector
{
    public function onCreated(FragmentCreated $event): void
    {
        $today = now()->format('Y-m-d');
        $type = $event->fragment->type ?? 'unknown';
        
        // Check if record exists first
        $existing = DB::table('fragment_metrics_daily')
            ->where('day', $today)
            ->where('type', $type)
            ->first();

        if ($existing) {
            DB::table('fragment_metrics_daily')
                ->where('day', $today)
                ->where('type', $type)
                ->update(['created' => $existing->created + 1]);
        } else {
            DB::table('fragment_metrics_daily')->insert([
                'day' => $today,
                'type' => $type,
                'created' => 1,
                'updated' => 0,
                'deleted' => 0,
            ]);
        }
    }

    public function onUpdated(FragmentUpdated $event): void
    {
        $today = now()->format('Y-m-d');
        $type = $event->fragment->type ?? 'unknown';
        
        $existing = DB::table('fragment_metrics_daily')
            ->where('day', $today)
            ->where('type', $type)
            ->first();

        if ($existing) {
            DB::table('fragment_metrics_daily')
                ->where('day', $today)
                ->where('type', $type)
                ->update(['updated' => $existing->updated + 1]);
        } else {
            DB::table('fragment_metrics_daily')->insert([
                'day' => $today,
                'type' => $type,
                'created' => 0,
                'updated' => 1,
                'deleted' => 0,
            ]);
        }
    }

    public function onDeleted(FragmentDeleted $event): void
    {
        $today = now()->format('Y-m-d');
        $type = $event->fragment->type ?? 'unknown';
        
        $existing = DB::table('fragment_metrics_daily')
            ->where('day', $today)
            ->where('type', $type)
            ->first();

        if ($existing) {
            DB::table('fragment_metrics_daily')
                ->where('day', $today)
                ->where('type', $type)
                ->update(['deleted' => $existing->deleted + 1]);
        } else {
            DB::table('fragment_metrics_daily')->insert([
                'day' => $today,
                'type' => $type,
                'created' => 0,
                'updated' => 0,
                'deleted' => 1,
            ]);
        }
    }
}