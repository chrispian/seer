# Schema (SQL/Migrations)

> Postgres preferred; MySQL notes included.

## `schedules`
```sql
CREATE TABLE schedules (
  id UUID PRIMARY KEY,
  user_id UUID NOT NULL,
  workspace_id UUID NULL,
  slug TEXT NOT NULL,                 -- human label, e.g., "morning-ai-news"
  command_slug TEXT NOT NULL,         -- e.g., "news-digest-ai"
  payload JSONB NOT NULL DEFAULT '{}',

  schedule_kind TEXT NOT NULL CHECK (schedule_kind IN ('one_off','daily_at','cron','rrule')),
  tz TEXT NOT NULL DEFAULT 'America/Chicago',

  -- for 'one_off'
  run_at_local TIMESTAMPTZ NULL,

  -- for 'daily_at'
  daily_local_time TEXT NULL,         -- "07:00"

  -- for cron/rrule (v0.0.2)
  cron_expr TEXT NULL,
  rrule TEXT NULL,

  next_run_at TIMESTAMPTZ NULL,       -- always UTC
  last_run_at TIMESTAMPTZ NULL,

  status TEXT NOT NULL DEFAULT 'active',  -- active|paused|completed|canceled
  run_count INT NOT NULL DEFAULT 0,
  max_runs INT NULL,                      -- optional cap

  locked_at TIMESTAMPTZ NULL,
  lock_owner TEXT NULL,
  last_tick_at TIMESTAMPTZ NULL,

  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_schedules_due ON schedules (next_run_at) WHERE status = 'active';
CREATE INDEX idx_schedules_user ON schedules (user_id);
```

## `schedule_runs`
```sql
CREATE TABLE schedule_runs (
  id UUID PRIMARY KEY,
  schedule_id UUID NOT NULL REFERENCES schedules(id) ON DELETE CASCADE,
  planned_run_at TIMESTAMPTZ NOT NULL,
  started_at TIMESTAMPTZ,
  finished_at TIMESTAMPTZ,
  status TEXT NOT NULL DEFAULT 'queued',   -- queued|running|ok|failed|skipped
  error TEXT NULL,
  output_ref TEXT NULL,                    -- optional pointer to artifact/fragment
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Idempotency guard
CREATE UNIQUE INDEX uq_schedule_runs_once ON schedule_runs (schedule_id, planned_run_at);
```

## MySQL Notes
- Use `JSON` type.
- Use `NOW(6)` and `datetime(6)` for precision.
- Emulate partial index with compound or generated column `is_active`.
```sql
ALTER TABLE schedules ADD COLUMN is_active TINYINT(1) AS (status = 'active') STORED, ADD INDEX idx_schedules_due (is_active, next_run_at);
```
