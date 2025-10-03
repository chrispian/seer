<?php

namespace App\Listeners\Projectors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\FragmentAccepted;
use App\Events\FragmentArchived;

class FragmentProjector
{
    public function onAccepted(FragmentAccepted $e): void
    {
        // Append to fragment_activity
        DB::table('fragment_activity')->insert([
            'id' => (string) Str::uuid(),
            'fragment_id' => $e->fragmentId,
            'action' => 'accepted',
            'by_user' => $e->userId,
            'payload' => json_encode($e->updates),
            'ts' => now(),
        ]);

        // Update daily metrics row
        $frag = DB::table('fragments')->where('id', $e->fragmentId)->first();
        if ($frag) {
            $inboxAt = $frag->inbox_at ?? $frag->created_at;
            $reviewedAt = $frag->reviewed_at ?? now();
            $ms = max(0, (strtotime($reviewedAt) - strtotime($inboxAt)) * 1000);
            $day = date('Y-m-d', strtotime($reviewedAt));

            // upsert
            $row = DB::table('inbox_metrics_daily')->where('day', $day)->first();
            if ($row) {
                DB::table('inbox_metrics_daily')->where('day',$day)->update([
                    'accepted_count' => $row->accepted_count + 1,
                    'review_time_ms_sum' => $row->review_time_ms_sum + $ms,
                    'review_time_ms_count' => $row->review_time_ms_count + 1,
                ]);
            } else {
                DB::table('inbox_metrics_daily')->insert([
                    'day' => $day,
                    'accepted_count' => 1,
                    'archived_count' => 0,
                    'review_time_ms_sum' => $ms,
                    'review_time_ms_count' => 1,
                ]);
            }
        }
    }

    public function onArchived(FragmentArchived $e): void
    {
        $day = date('Y-m-d');
        $row = DB::table('inbox_metrics_daily')->where('day', $day)->first();
        if ($row) {
            DB::table('inbox_metrics_daily')->where('day',$day)->update([
                'archived_count' => $row->archived_count + count($e->fragmentIds),
            ]);
        } else {
            DB::table('inbox_metrics_daily')->insert([
                'day' => $day,
                'accepted_count' => 0,
                'archived_count' => count($e->fragmentIds),
                'review_time_ms_sum' => 0,
                'review_time_ms_count' => 0,
            ]);
        }

        foreach ($e->fragmentIds as $fid) {
            DB::table('fragment_activity')->insert([
                'id' => (string) Str::uuid(),
                'fragment_id' => $fid,
                'action' => 'archived',
                'by_user' => $e->userId,
                'payload' => json_encode(null),
                'ts' => now(),
            ]);
        }
    }
}
