# Timezones & Idempotency Notes

## Timezones
- Always compute **target local time** in the schedule TZ, then convert to UTC for `next_run_at`.
- After each run, compute the next *local* occurrence (not simply +24h) to respect DST.

## Idempotency
- `schedule_runs(schedule_id, planned_run_at)` unique index prevents double-recording.
- Ticker claims with a lock and writes a `queued` run record before enqueue.
- Job updates the run record to `running`/`ok`/`failed`.

## Clock Skew
- Use DB server time for comparisons; avoid relying on app host time.
