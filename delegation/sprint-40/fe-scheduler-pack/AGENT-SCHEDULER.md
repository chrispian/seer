# Fragments Engine — Scheduler (MVP) Agent Pack

**Scope:** Backend-only MVP to schedule recurring and one-off tasks that execute Command Packs (e.g., `/news-digest-ai`, `/remind`). No UI yet. Runs daily/weekly/etc. and enqueues command executions.

**Owner:** Chrispian | **Targets:** v0.0.1 (MVP scheduler), v0.0.2 (quality + ergonomics)

---

## Mission Objectives (v0.0.1)
1. Implement a **lightweight scheduler loop** (`frag:scheduler:tick`) that runs every minute.
2. Add `schedules` + `schedule_runs` tables with timezone-aware execution and idempotency.
3. Integrate with the **Slash Command Runner**: scheduled tasks enqueue a command with a serialized context/payload.
4. Provide two **built-in command packs** for demo:
   - `/news-digest-ai` — collects AI news in the last 24h at 07:00 local, produces top 10–15 newsletter.
   - `/remind` — one-off reminders (“Next Monday 9am: refill meds”).

---

## High-Level Flow
- A cron/systemd timer invokes `php artisan frag:scheduler:tick` every minute.
- The ticker **claims due schedules atomically**, enqueues a `RunCommandJob` with the stored slug/payload, then updates `next_run_at`.
- `RunCommandJob` executes the DSL runner for the referenced Command Pack (`slug`), with the stored `payload` merged into `ctx.*`.
- `schedule_runs` records each run (status, durations, output refs).

See `01-architecture.md` and `02-schema.sql.md` for details.
