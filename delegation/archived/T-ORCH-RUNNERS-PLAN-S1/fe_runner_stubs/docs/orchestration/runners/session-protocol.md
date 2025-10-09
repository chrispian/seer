# Session Protocol (Attached Runner)

Events:
- `SESSION_OPENED`, `MODEL_DELTA`, `TOOL_CALL`, `TOOL_RESULT`,
- `RUN_OUTPUT`, `CHECKPOINT`, `HEARTBEAT`, `SESSION_CLOSED`.

Reattach: client provides `{ conversation_id, latest_event_id }` to resume stream.
