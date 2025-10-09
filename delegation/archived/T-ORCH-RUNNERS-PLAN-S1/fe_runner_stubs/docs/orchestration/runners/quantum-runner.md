# Quantum Runner (Pattern A)

**Loop:** claim lease → run for a fixed quantum (time/tool/token budgets) → checkpoint → yield → requeue.

- **Lease key**: `lease:{task_id}:{run_id}` (TTL ~120s).
- **Budgets**: `{ tokens, tool_calls, wall_seconds }` → on exceed: checkpoint + yield.
- **Buffered telemetry**: flush on checkpoint or every N seconds.
- **Idempotency**: `(task_id, run_id, step_id, attempt)`.
