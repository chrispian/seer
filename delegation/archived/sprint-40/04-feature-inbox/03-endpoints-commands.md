# Endpoints & Slash Commands (Backend)

## REST-ish Endpoints (MVP)
- `GET /api/inbox`  
  Query params: `status=pending|accepted|archived`, `type`, `tag`, `category`, `vault`, `q`, `sort` (`created_at|captured_at|type`), `order`, `limit`, `cursor`.
- `POST /api/inbox/accept`  
  Body: `{ ids: [ulid], edits?: { tags?, category?, type?, vault?, title?, content?, state? }, note?: string }`
- `POST /api/inbox/accept-all`  
  Body: same `edits?` as above, applies to current filtered result set (server re-runs filter).
- `POST /api/inbox/archive`  
  Body: `{ ids: [ulid], note?: string }`
- `POST /api/inbox/reopen`  
  Body: `{ ids: [ulid] }`

Each mutation persists:
- fragment field changes
- `reviewed_at=now()`, `reviewed_by=user_id`
- `inbox_status` transition
- append an audit record (see 08-audit.md)

## Slash Commands (Command Packs)
- `/inbox` — quick list summary (count by type, top N pending).
- `/accept {id|ids|all}` — accept; optional flags for `--tag`, `--type`, `--category`, `--vault`.
- `/archive {id|ids|all}` — mark archived (skip for now).
- `/tag {id} +dev -personal` — add/remove tags while pending.

Command Pack manifests can call endpoints or directly use services.
