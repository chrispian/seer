# Read‑Models (Optional Future)

## Why
- Lightning dashboards and exports without touching canonical writes.

## Pattern
- Projector listens to `FragmentCreated/Updated`.
- For types you care about, shape rows in `{type}_read` tables.

## Example: `todo_read`
```sql
CREATE TABLE todo_read (
  fragment_id UUID PRIMARY KEY,
  title TEXT,
  status VARCHAR(32),
  due_at TIMESTAMPTZ NULL,
  project_id UUID NULL,
  tags TEXT[],
  updated_at TIMESTAMPTZ NOT NULL
);
CREATE INDEX idx_todo_read_due ON todo_read (due_at);
CREATE INDEX idx_todo_read_status ON todo_read (status);
```

Backfill = replay from fragments. Safe to drop/rebuild.
