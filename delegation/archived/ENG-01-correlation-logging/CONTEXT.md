# Context: Correlation Logging

## Current Logging State
- Logs scattered across actions (`ParseChaosFragment`, `ParseAtomicFragment`, etc.) using `Log::debug/info/error` without consistent context.
- No correlation ID tying HTTP request to queued fragment child processing.
- Fragment metadata stores `chaos_lineage` but not used for log correlation.

## Key Touchpoints
- Entry: `FragmentController@store` (HTTP request).
- Queue dispatch: anonymous closures in `ParseChaosFragment`; jobs like `EmbedFragment`.
- AI services log provider/model info but without shared ID.

## Goals
- Generate correlation ID per capture request, store in fragment metadata.
- Propagate ID through pipeline and into queued jobs.
- Use structured logging (e.g., `Log::withContext` + JSON) to include IDs, provider/model, timing.

## Considerations
- Laravel provides middleware (`LogRequestId`, custom) for request IDs—reuse or extend.
- For queued jobs, pass correlation ID via job constructor or `dispatch` context.
- Consider using `Illuminate\Log\Context` or custom logger wrapper for convenience.
- Document best practices for developers when logging new actions.

## Dependencies
- None blocking; ensure compatibility with future telemetry dashboard (Phase 3).

## Definition of Done
- Refer to PLAN; ensure tests and docs cover new logging pipeline.
