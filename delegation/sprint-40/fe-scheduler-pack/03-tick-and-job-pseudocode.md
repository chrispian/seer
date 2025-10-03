# Tick Loop & Job — Pseudocode

## Command: frag:scheduler:tick
```php
public function handle()
{
    $now = Carbon::now('UTC');
    $claimed = Schedule::claimDue($now, $limit = 50); // atomic claim

    foreach ($claimed as $sch) {
        DB::transaction(function() use ($sch, $now) {
            ScheduleRun::firstOrCreate([
                'schedule_id' => $sch->id,
                'planned_run_at' => $sch->next_run_at,  // idempotency
            ], ['status' => 'queued']);

            dispatch(new RunCommandJob($sch->id, $sch->command_slug, $sch->payload, $sch->next_run_at));
            // Update next_run_at *after* enqueue to reduce duplicate claims
            $sch->computeAndPersistNextRun();
        });
    }
}
```

## Model: Schedule::claimDue
- PG example (CTE + RETURNING):
```sql
WITH cte AS (
  SELECT id FROM schedules
  WHERE status='active'
    AND next_run_at IS NOT NULL
    AND next_run_at <= now()
    AND (locked_at IS NULL OR locked_at < now() - interval '5 minutes')
  ORDER BY next_run_at ASC
  LIMIT 50
  FOR UPDATE SKIP LOCKED
)
UPDATE schedules s
SET locked_at = now(), lock_owner = :owner, last_tick_at = now()
FROM cte
WHERE s.id = cte.id
RETURNING s.*;
```

## Job: RunCommandJob
```php
public function handle()
{
    $ctx = [
      'schedule'  => ['id'=>$this->scheduleId, 'planned_run_at'=>$this->plannedAt],
      'payload'   => $this->payload,
      'now'       => Carbon::now('UTC')->toIso8601String(),
      'workspace' => // hydrate minimal workspace context,
      'user'      => // hydrate minimal user context,
    ];

    $run = ScheduleRun::where('schedule_id', $this->scheduleId)
          ->where('planned_run_at', $this->plannedAt)->first();
    $run->update(['status'=>'running','started_at'=>now()]);

    try {
        $result = $this->dslRunner->run($this->commandSlug, $ctx);
        $run->update(['status'=>'ok','finished_at'=>now(),'output_ref'=>$result->ref ?? null]);
    } catch (\Throwable $e) {
        $run->update(['status'=>'failed','finished_at'=>now(),'error'=>$e->getMessage()]);
        // leave schedule active; rely on schedule to compute next_run_at
        throw $e;
    }
}
```

## NextRunCalculator
- For `one_off`: set `status='completed'` after the run.
- For `daily_at`: compute the next local `HH:mm` in schedule TZ, then convert to UTC.
