# ENG-01 Phase 1: Correlation Logging Plan

## Objective
Introduce correlation IDs and structured context logging throughout the fragment pipeline so each fragment’s journey (HTTP request → queued jobs) can be traced consistently.

## Deliverables
- Correlation ID generation strategy (incoming request + child fragments) with propagation across synchronous and queued steps.
- Structured logs (JSON) for key actions (`ParseChaosFragment`, `ParseAtomicFragment`, `EnrichFragmentWithLlama`, `InferFragmentType`, etc.) including correlation ID, fragment ID, parent IDs, model metadata.
- Logging helpers (trait/service) to attach correlation IDs automatically.
- Documentation describing how to trace a fragment through logs and how IDs are propagated.
- Updated tests (unit/integration) asserting correlation IDs are present on logged events.

## Work Breakdown
1. **Discovery**
   - Review current logging usage across pipeline actions.
   - Identify entry points: HTTP controller, queue jobs, command handlers.
2. **Design Correlation Strategy**
   - Choose ID format (UUID v4) and storage (fragment metadata + `Log::withContext`).
   - Determine propagation path for queued jobs (e.g., `dispatch` middleware, job payload).
3. **Implementation**
   - Add middleware/service to stamp incoming requests with correlation ID.
   - Update pipeline actions/jobs to include correlation ID in context (structured logging or `Log::withContext`).
   - Ensure child fragments inherit parent correlation lineage.
4. **Testing & Validation**
   - Write tests ensuring correlation IDs persist across pipeline actions and queued jobs.
   - Manually trigger pipeline in dev to confirm correlated logs.
5. **Documentation**
   - Document tracing process within `docs/pipeline/` folder.

## Acceptance Criteria
- Every log from pipeline actions contains `correlation_id`, `fragment_id`, and when applicable `parent_fragment_id`.
- Queued jobs output the same correlation ID as initial request.
- Documentation describes tracing workflow; tests validate ID propagation.

## Risks
- Ensure correlation IDs don’t conflict with queue serialization.
- Avoid overly verbose logs; keep context focused.
