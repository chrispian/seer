# ENG-01 Pipeline Audit — Implementation Plan

## Objective
Map the fragment ingestion/enrichment pipeline end-to-end, evaluate model usage and determinism, and recommend improvements for type inference, tagging, metadata extraction, and logging.

## Deliverables
- Pipeline diagram + narrative documenting each stage (sync vs async, inputs/outputs, dependencies).
- Prompt and configuration review for AI-powered steps with proposed refinements (system prompts, temperature/seed settings, deterministic fallbacks).
- Model fit analysis recommending the right provider/model per classifier/enricher, including fallback strategy.
- Improvement backlog covering type inference, tagging, metadata extraction, logging, and testing.
- Updated documentation (PLAN.md summary), tickets/TODOs noted in `PROJECT_PLAN.md` if needed.

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin` / `git pull --rebase origin main` (report sandbox blocks if encountered).
   - Documentation-focused task; no code changes expected unless capturing findings in repo docs.

2. **Current-State Mapping**
   - Trace capture flow: FragmentController → ParseChaosFragment → ParseAtomicFragment → ExtractMetadataEntities → EnrichFragmentWithLlama → InferFragmentType → SuggestTags → RouteToVault → EmbedFragmentAction.
   - Note queues, retries, and where metadata is stored/updated.
   - Produce sequence diagram or markdown table summarising each step.

3. **Prompt & Determinism Audit**
   - Review prompts in ParseChaosFragment, EnrichFragmentWithLlama, TypeInferenceService, etc.
   - Evaluate temperature/top_p defaults, JSON enforcement, and error handling.
   - Recommend system prompts or deterministic wrappers (e.g., guard rails, JSON schema validation).

4. **Model Selection Assessment**
   - Analyse `ModelSelectionService` strategy for text vs embeddings.
   - Determine optimal models per task (e.g., classification vs enrichment) considering latency, cost, determinism.
   - Suggest specialized providers/models (OpenAI GPT-4o-mini, Claude Haiku, local llama) per use case.

5. **Metadata & Tagging Improvements**
   - Examine `ExtractMetadataEntities`, `SuggestTags`, and metadata schema.
   - Identify opportunities to enhance tagging/type confidence (e.g., AI-assisted extraction, expand regex patterns, add confidence fields).
   - Recommend structured storage for provenance and lineage (e.g., model name, confidence scores).

6. **Operational Hardening**
   - Review logging (capture correlation IDs, errors) and telemetry needs.
   - Identify missing tests (unit fixtures for prompt parsing, integration tests for pipeline steps).
   - Propose metrics and dashboards for pipeline stages.

7. **Synthesis & Recommendations**
   - Compile findings into a report (markdown) with prioritized action items.
   - Update `PROJECT_PLAN.md` or open issues for follow-up tasks (type inference model swap, tag improvements, etc.).

## Acceptance Criteria
- Comprehensive documentation of current pipeline with diagrams/notes.
- Clear list of prompt/model adjustments tied to determinism and quality goals.
- Actionable recommendations for type inference, tagging, metadata, logging, and testing improvements.
- No code changes unless necessary to document findings (e.g., README update).

## Risks & Notes
- Keep scope analytical; defer implementation tasks to future tickets.
- Ensure documentation is easily discoverable (e.g., commit under `docs/pipeline/`).
- Coordinate with AI-01 provider abstraction outputs when suggesting model changes.
