# Migrations (Postgres)

```sql
ALTER TABLE fragments
  ADD COLUMN IF NOT EXISTS inbox_status TEXT NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS inbox_reason TEXT NULL,
  ADD COLUMN IF NOT EXISTS inbox_at TIMESTAMPTZ NULL,
  ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMPTZ NULL,
  ADD COLUMN IF NOT EXISTS reviewed_by UUID NULL;

-- Backfill
UPDATE fragments SET inbox_at = COALESCE(inbox_at, created_at) WHERE inbox_at IS NULL;

-- Indexes
CREATE INDEX IF NOT EXISTS idx_fragments_inbox_status ON fragments (inbox_status);
CREATE INDEX IF NOT EXISTS idx_fragments_inbox_pending_type_created ON fragments (type, created_at) WHERE inbox_status='pending';
```

**MySQL 8**
- Use `datetime(6)` and generated column to emulate partial index if desired.
