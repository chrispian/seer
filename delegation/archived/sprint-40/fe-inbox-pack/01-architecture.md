# Architecture

## Data Model (fragments)
Add the following columns if not already present:
- `inbox_status`: `pending|accepted|archived|skipped` (default `pending`)
- `inbox_reason`: TEXT NULL (why it entered inbox; e.g., "capture:email", "manual", "import")
- `inbox_at`: TIMESTAMPTZ (defaults to `created_at` on insert)
- `reviewed_at`: TIMESTAMPTZ NULL
- `reviewed_by`: UUID NULL (user id)
- `edited_message`: TEXT NULL (already exists; used for quick edits before apply)

### Notes
- Keep **fragments** as source of truth. Inbox is a set of flags/fields on the fragment (no separate table).
- Deterministic MVP: acceptance mutates only the fields supplied by the user.

## State Machine (MVP)
`pending` → (accept) → `accepted`  
`pending` → (archive/skip) → `archived|skipped`  
`accepted` → (reopen) → `pending` (optional)

## Indexing
- `inbox_status` BTREE
- Partial composites for common filters:
  - `(type, created_at)` WHERE `inbox_status='pending'`
  - GIN on `tags` if array  
