# Import System Overview

## Goals
- Capture daily activity from developer tooling (OpenCode, Claude desktop/projects, Codex CLI) for analytics and support.
- Normalise heterogeneous log formats into the `agent_logs` table with consistent metadata (timestamps, sessions, providers, models).
- Provide a single Artisan entry point (`logs:import`) that can be scheduled via cron and exercised manually with filtering options.

## Primary Entry Point: `php artisan logs:import`
- **Sources**: defaults to `opencode`, `claude`, `codex`; limit via `--source=` (array option).
- **Windowing**: `--since=` accepts absolute dates (`2025-10-06`) or relative strings (`yesterday`). Files modified before the window are skipped up front.
- **Dry Runs**: `--dry-run` executes the full parse/dedupe pipeline without inserting rows, while still reporting stats per source.
- The command resolves to `App\Services\AgentLogImportService`, which performs all parsing, transformation, and persistence work.

## Source Layout & Parsing
- **OpenCode**
  - Location: `~/.local/share/opencode/log/*.log` (plain text).
  - Parser: Regex for `LEVEL TIMESTAMP +offset service= message`; extracts provider/model hints from the free-text payload.
- **Claude Desktop**
  - Location: `~/Library/Logs/Claude/*.log` (plain text, two formats for desktop vs MCP files).
  - Parser: Timestamped lines with level/service; session IDs extracted via regex.
- **Claude Projects**
  - Location: `~/.claude/projects/**/session.jsonl` (JSONL).
  - Parser: JSON decode of each line, with helper that normalises messages containing structured content arrays.
- **Codex CLI**
  - Location: `~/.codex/sessions/**.jsonl` (JSONL).
  - Parser: JSON decode with specialised summarisation per event type (session meta, request, response, tool usage).

Each parser builds an associative array with:
- `source_type`, `source_file`, `file_line_number`, `file_checksum`.
- Core metadata: `log_timestamp`, `log_level`, `service`, `message`, `structured_data`.
- Enriched fields when available: `session_id`, `provider`, `model`, `tool_calls`.

## Deduplication Strategy (2025-10-06)
- `AgentLogImportService::hasExistingLogEntry()` now checks before every insert using:
  - `source_type`, `source_file`, `log_timestamp`, and `file_line_number` (falling back to message match when a line number is missing).
  - Optional `session_id` to differentiate simultaneous sessions in the same file.
- If a row already exists, the importer increments `entries_skipped` and continues without touching the row set.
- Result: repeated runs of the importer no longer duplicate historical lines even when a log file gains new trailing entries or is re-hashed.
- Validation query: `select * from agent_logs group by source_type, source_file, file_line_number, log_timestamp having count(*) > 1` currently returns zero rows across all sources.

## Scheduling & Operations
- Nightly automation is handled by a system cron (external to Laravel) invoking `php artisan logs:import`. Midnight failures surface in `storage/logs/laravel.log`.
- Manual reruns: execute the same command locally. Use `--dry-run` first when validating.
- For diagnostics:
  - Source counts: `select source_type, max(created_at), count(*) from agent_logs group by source_type;`
  - Recent ingests: check `entries_imported` vs `entries_skipped` output in the Artisan command.

## Recent Fixes (2025-10-06)
- Resolved `strlen()` TypeError when Claude Project messages delivered nested arrays.
- Added UTF-8 safe truncation via `mb_substr` to avoid invalid byte sequences.
- Added cross-source dedupe guard to ensure nightly cron jobs only persist new lines.
- Registered `orch:task-list` artisan alias in `routes/console.php` to unblock ambiguous cron invocations (legacy namespace support).

## Suggested Next Steps
1. **Persist File Import State**
   - Store the last imported checksum per `source_file` in a dedicated table to avoid scanning the full file when no changes exist.
2. **Signature Index**
   - Materialise an `entry_hash` (e.g. sha256 of `source_type|source_file|file_line_number|log_timestamp`) with a unique index to make dedupe constant-time.
3. **Parallelisation**
   - Split large OpenCode imports into chunked jobs using queues to reduce single run wall-clock time.
4. **Monitoring**
   - Emit metrics (e.g. statsd or Horizon tags) with per-source counts to detect sudden spikes.
5. **Test Coverage**
   - Add Pest coverage for the parser helpers with fixture-backed JSON/text samples to lock in edge cases (multi-byte chars, nested arrays).

For questions or operational follow-ups, ping the Platform/AI tooling channel.
