# Fragment Processing Pipeline Review

## Overview
- Convert the pipeline assessment into actionable sprints that optimize chat-driven fragment ingestion for determinism, cost control, and reuse.
- Plan delivery for Context Broker, Tool Broker, revived embeddings, and Agent Broker so chat sessions rely on unified services instead of bespoke logic.

## Current Flow Snapshot
- Chat composer posts markdown to `/api/messages`, then streams `/api/chat/stream/{message_id}` (`resources/js/islands/chat/ChatIsland.tsx:123-210`).
- `ChatApiController@send` persists the user prompt via `CreateChatFragment`, which normalizes input and stores a `type: log` fragment without enrichment (`app/Http/Controllers/ChatApiController.php:13-94`, `app/Actions/CreateChatFragment.php:12-40`).
- Session context cached for the LLM is just `[system("You are a helpful assistant"), user(prompt)]` with TODOs for history/system prompts (`ChatApiController.php:57-63`, `CacheChatSession.php:11-25`).
- Assistant reply flows through `ProcessAssistantFragment` → `RouteFragment` (dedupe) → async pipeline (`ExtractJsonMetadata`, `EnrichAssistantMetadata`, `DriftSync`, `InferFragmentType`, `SuggestTags`) but skips embeddings (`ProcessAssistantFragment.php:47-81`).
- General fragment pipeline (`ProcessFragmentJob`) includes deterministic analyzers, AI enrichment, routing, and embedding but is never invoked for chat fragments (`ProcessFragmentJob.php:53-64`).
- Attachments submitted from the UI are ignored server-side—no storage, metadata, or embeddings (`ChatApiController.php:13-94`).

## Key Gaps vs Desired Behaviour
- Chat fragments bypass classification, tagging, vault routing, and embeddings; normalized text is saved without leveraging deterministic analyzers (`CreateChatFragment.php:25-32`, `ProcessFragmentJob.php:53-65`).
- Context assembly is minimal; there is no broker merging system prompts, chat history, memory, or project/task context (`ChatApiController.php:57-115`).
- Tool/command routing requires explicit slash commands—no natural language broker to map intents to existing automations (`resources/js/islands/chat/ChatComposer.tsx:67-120`, `app/Services/Tools/ToolRegistry.php:13-106`).
- Embeddings disabled by default, and chat fragments never enqueue `EmbedFragmentAction`, so vector recall is empty (`config/fragments.php:5-35`, `EmbedFragmentAction.php:21-83`).
- Assistant metadata enrichment expects `<<<JSON_METADATA>>>` blocks that the current prompts never request, so extra parsing work rarely yields data (`ExtractJsonMetadata.php:32-71`).
- Cost savings/determinism limited: AI enrichment and type inference are invoked even when rule-based results exist (`ProcessFragmentJob.php:60-62`, `TypeInferenceService.php:29-129`).

## Recommendations & Future Architecture
- **Unify ingestion**: Route both user and assistant chat fragments through a shared orchestrator (wrap `ProcessFragmentJob`) so deterministic steps run immediately and AI enrichers/embeddings queue asynchronously.
- **Context Broker**: Build a service that assembles system/agent prompts, trims history, injects project/task/memory context, and returns a finalized message list before streaming (`ChatApiController.php:57-115`). Log broker outputs for debugging.
- **Tool Broker**: After fragment creation, run rule-based + cheap-model intent classification; invoke DSL commands/`ToolRegistry` when matches exist, and log unmatched intents to suggest new commands. Ensure deterministic routing records metrics.
- **Embedding Revival**: Enable embeddings (`EMBEDDINGS_ENABLED=true`), prefer local/cheaper models via `ModelSelectionService`, and queue `EmbedFragmentAction` on all chat fragments (user + assistant).
- **Agent Broker**: Extend model selection with deterministic rules (project, vault, task) and expose agent choices back to the UI; fall back to AI routing only on low-confidence paths.
- **Attachment Handling**: Persist uploads, link them to fragments, and include them in embedding/context pipelines so the system can reuse deterministic signals (e.g., extracted text, metadata).
- **Prompt Discipline**: Update assistant request prompts to include deterministic metadata directives (or drop the parser) and clearly separate summary vs JSON sections to reduce token waste.

## Suggested Sprint Seed Items
1. **Pipeline Unification**: Create a `FragmentProcessingOrchestrator`; update chat controllers to dispatch it for both user and assistant fragments; ensure deterministic analyzers execute inline with optional async AI steps.
2. **Context Broker v1**: Implement service + config scaffolding, replace hard-coded history, and expose metrics; integrate memory/embedding lookups as soon as embeddings are available.
3. **Tool Broker MVP**: Add intent classification to chat ingestion, wire into `CommandRunner`/`ToolRegistry`, add logging + analytics for new-command suggestions.
4. **Embeddings Reboot**: Enable config, confirm Ollama/local provider support, validate `fragment_embeddings` writes, and expose combined vector + fulltext search to memory/recall.
5. **Agent Broker Rules**: Layer deterministic agent selection on top of `ModelSelectionService`, add overrides in Chat UI, and log routing decisions.
6. **Attachment Support**: Persist uploads, attach metadata, and push through enrichment + embeddings; extend context broker to include attachment summaries in prompts.
7. **Prompt Cleanup & Cost Controls**: Update system prompts to request only necessary structured data; add guards to skip AI enrichment/type inference when deterministic confidence is high.

Use this file as the foundation for sprint decomposition and ticket creation.

## Logging & Telemetry Audit
- **Chat ingestion gaps**: `app/Http/Controllers/ChatApiController.php:11-160` has no structured logging, so we lose message IDs, user/session context, attachment payload stats, or validation failures; `app/Actions/CacheChatSession.php:10-34` and `app/Actions/RetrieveChatSession.php:12-42` also run silently which makes it hard to trace cache hits/expirations.
- **Assistant streaming**: `app/Actions/StreamChatProvider.php:10-76` emits useful start/finish/error logs, but the payload lacks `conversation_id`, `session_id`, latency buckets, or token counts; downstream `app/Actions/ProcessAssistantFragment.php:10-84` only logs debug statements without provider/model/cost data or step outcomes.
- **Enrichment pipeline**: Steps such as `app/Actions/ExtractJsonMetadata.php:11-63`, `app/Actions/EnrichAssistantMetadata.php:13-120`, and `app/Actions/SuggestTags.php:21-53` do not log successes/failures or emit metrics, so we can’t audit classification/tagging accuracy or capture “why” decisions; consider a shared telemetry helper that records step name, inputs, outputs, duration, and error state.
- **Fragment orchestration**: `app/Jobs/ProcessFragmentJob.php:33-102` logs start/finish using emoji strings and omits key identifiers (conversation, source, vault, model); standardize on structured JSON with correlation IDs to make log aggregation feasible.
- **Command execution (hard-coded)**: `app/Http/Controllers/CommandController.php:14-220` writes success data only to fragments; logging is limited to failures, so we lack real-time dashboards on command frequency, latency, or user context.
- **DSL runner**: `app/Services/Commands/DSL/CommandRunner.php:17-123` records slow-command warnings but skips normal success/failure logs; per-step metadata (start/end, rendered config, dry-run vs live) stays in-memory and never reaches logs/metrics.
- **DSL steps**: Except for `AiGenerateStep` and `NotifyStep`, most steps (e.g. `DatabaseUpdateStep`, `FragmentUpdateStep`, `ListMapStep`) complete silently; add consistent logging hooks or emit `StepStarted`/`StepCompleted` events with `status`, `duration_ms`, and relevant IDs.
- **Tool telemetry**: `app/Services/Commands/DSL/Steps/ToolCallStep.php:20-109` does capture invocation rows + events (good), but it still lacks structured log lines tying tool calls back to chat/command correlation IDs.
- **Middleware**: `app/Http/Middleware/EnsureDefaultUser.php:14-43` and `app/Http/Middleware/EnsureUserSetupComplete.php:18-58` run without logging, so auto-login and setup redirects are invisible in traces; a request-scoped context bag (session id, user id, request path) would help.
- **Chain-of-thought & prompts**: No module currently captures reasoning metadata or prompt templates; when we introduce it, ensure opt-in storage, redaction of sensitive content, and tie entries to message/command IDs for later prompt analysis.

### Immediate Logging Enhancements to Queue
1. Instrument chat send/stream with structured logs (`level`, `message_id`, `conversation_id`, `session_id`, `provider`, `model`, `user_id`, `attachments_count`, `latency_ms`, `token_usage`).
2. Wrap fragment/assistant pipelines with a telemetry decorator that tracks each stage (start → end → status, errors, output summary) and emits events + logs.
3. Extend `CommandController`/`CommandRunner` to push execution metrics (command slug, user, run mode, duration, step statuses) to logs and analytics, mirroring what `ToolCallStep` writes to the database.
4. Introduce a request-scoped correlation ID middleware so logs from middleware → chat controllers → pipelines → DSL steps share a common identifier.
5. Plan storage/anonymization strategy for prompt/chain-of-thought capture before enabling provider features, to avoid leaking sensitive memory while still supporting analysis.
