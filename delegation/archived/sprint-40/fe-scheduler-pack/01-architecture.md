# Scheduler Architecture (MVP)

## Components
- **Table: `schedules`** — canonical schedule definitions.
- **Table: `schedule_runs`** — audit of each execution attempt.
- **Command: `frag:scheduler:tick`** — minute-level loop.
- **Job: `RunCommandJob`** — executes a command pack by slug with payload.
- **Helper: `NextRunCalculator`** — cron/rrule-based next-run computation.

## Claiming Due Work (Atomic)
Use a single UPDATE ... RETURNING (PG) / UPDATE + SELECT (MySQL) to lock the row:
- Conditions: `status='active' AND next_run_at <= now() AND (locked_at IS NULL OR locked_at < now() - interval '5 minutes')`
- Set: `locked_at = now()`, `lock_owner = hostname:pid`, `last_tick_at = now()`
- Process claimed rows sequentially or burst-limited.

## Idempotency
- `schedule_runs` has a unique key on (`schedule_id`, `planned_run_at`) to avoid double-processing if a tick overlaps.
- Jobs check a `dedupe_key` derived from the pair above.

## Timezones
- Store **user TZ** per schedule (e.g., `America/Chicago`).
- Save `next_run_at` in UTC, compute with TZ-aware library.
- Daylight saving handled by converting user-local intended 07:00 → UTC at creation and after each run.

## Recurrence
- MVP supports:
  - `one_off` with `run_at_local` (TZ) → materialize `next_run_at` once.
  - `daily_at` `"07:00"` (TZ) → compute next local 07:00 after run.
- v0.0.2 adds `cron_expr` and/or `RRULE` (RFC 5545).

## Integration with Command Packs
- `command_slug`: which command to run (`news-digest-ai`, `remind`).
- `payload`: arbitrary JSON provided to DSL via `ctx.schedule.payload`.
- The runner merges: `{ ctx: { schedule: {...}, user, workspace, now } }`.
