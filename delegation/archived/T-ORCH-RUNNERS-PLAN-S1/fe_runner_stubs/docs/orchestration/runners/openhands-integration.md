# OpenHands Attached Runner

- **Transport:** WebSocket (Socket.IO) session per `(task_id, run_id)`.
- **Heartbeats:** runner → FE every 10s. Missed 3 = close + resume via checkpoint.
- **Artifacts:** logs, junit, patches streamed to `fe://` with manifests.
- **Policy:** all commands routed through FE policy (allowlist + budgets).
