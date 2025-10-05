# Chat Logging & Telemetry Deep Dive

## Overview
- Give the next agent enough detail to design a sprint that upgrades chat + command observability without storing raw user content (single local user, NativePHP runtime).
- Focus on capturing who/what/when/where/why via object keys (`user_id`, `fragment_id`, `command_slug`, etc.) and correlation identifiers rather than literal data.

## Findings
- Chat ingest (`app/Http/Controllers/ChatApiController.php:11-160`) and cache helpers (`app/Actions/CacheChatSession.php:10-34`, `app/Actions/RetrieveChatSession.php:12-42`) emit no logs, so we cannot trace message UUIDs, fragment IDs, or attachment stats; validation failures disappear silently.
- Assistant flow logs start/end messages (`app/Actions/StreamChatProvider.php:10-76`) but skip contextual keys: no `conversation_id`, cached `session_id`, or fragment relationship IDs; pipeline follow-up (`app/Actions/ProcessAssistantFragment.php:10-84`) logs only debug strings without indicating which enrichment steps ran or why they failed.
- Enrichment steps (`app/Actions/ExtractJsonMetadata.php:11-63`, `app/Actions/EnrichAssistantMetadata.php:13-120`, `app/Actions/SuggestTags.php:21-53`) keep decisions in memory; there is no per-step duration/status, nor do they note which fragment/object IDs were touched.
- Fragment orchestrator (`app/Jobs/ProcessFragmentJob.php:33-102`) still logs emoji strings without structured fields; we cannot correlate job retries or figure out which deterministic analyzers fired.
- Command layer: controller (`app/Http/Controllers/CommandController.php:14-220`) logs failures only; DSL runner (`app/Services/Commands/DSL/CommandRunner.php:17-123`) records slow-command warnings, and most steps (e.g. `DatabaseUpdateStep`, `FragmentUpdateStep`, `ListMapStep`) never emit telemetry even though they mutate fragments or trigger tools.
- Middleware (`app/Http/Middleware/EnsureDefaultUser.php:14-43`, `EnsureUserSetupComplete.php:18-58`) silently log in/redirect the single local user, so request-scoped context is missing from downstream logs.
- Tool invocations already persist to `tool_invocations` (good), but those rows lack upstream correlation keys (e.g. `message_id`, `command_run_id`) to tie UI actions to tool executions.
- No mechanism stores prompt variants or model “chain-of-thought” metadata; when we add it, we must redact content yet preserve object keys (`fragment_id`, `intent`, `agent_id`) for analysis.

## Suggestions
1. **Structured chat logging**: emit JSON logs on send/stream with (`message_id`, `fragment_id`, `conversation_id`, `session_id`, `provider`, `model`, `attachments_count`, `latency_ms`, `token_usage`, `status`). Since there’s only one local user, keep `user_id: 'local-default'` (or similar) to satisfy who without storing PII.
2. **Telemetry decorator**: wrap fragment + assistant pipelines with a reusable helper that records step name, start/end timestamps, duration, outcome, and any generated object keys (e.g. derived `fragment_id`, `tag_ids`). Persist to logs/events without copying raw text.
3. **Command/DSL metrics**: add execution logs in `CommandController` and `CommandRunner` capturing (`command_slug`, `execution_id`, `step_id`, `success`, `duration_ms`, `dry_run`). Mirror this in each DSL step (especially mutating ones) using object keys (`fragment_id`, `model`, `job_id`).
4. **Correlation ID middleware**: inject a UUID per request/stream, attach it to the request context, and include it in every log/event. Even in a single-user scenario, this “where/when” link is crucial for debugging chained jobs.
5. **Tool + prompt tracing**: extend `tool_invocations` schema/logging with upstream keys (`command_execution_id`, `message_id`, `context_id`). Prepare a storage policy for optional prompt/chain-of-thought captures that stores metadata keys plus hashed content references rather than raw strings.
6. **Metrics transport**: evaluate batching telemetry to a local sink (file, SQLite table, or event bus) so NativePHP users can inspect history offline; ensure fields stay key-based.

Use this brief to scope tickets for implementing structured telemetry while honoring the key-only requirement.
