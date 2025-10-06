# ENG-01 Pipeline Audit Context

## Current Pipeline Overview
1. **Fragment Creation** – `FragmentController@store` persists incoming text, defaults type to chaos or note.
2. **ParseChaosFragment** – splits multi-topic entries via Ollama `llama3`, queues child fragments through a follow-up pipeline.
3. **ParseAtomicFragment** – extracts explicit type prefixes, hashtags, mentions; sets initial tags, metadata, and default type.
4. **ExtractMetadataEntities** – regex-based extraction for people, URLs, dates, phones, etc.; merges into `parsed_entities` and metadata.
5. **EnrichFragmentWithLlama** – prompts AI (model selected via `ModelSelectionService`) to produce structured fragment JSON; temperature 0.3, minimal validation.
6. **InferFragmentType** – uses `TypeInferenceService` (AI classification with fallback heuristics) to set type/type_id if absent.
7. **SuggestTags** – simple keyword match to append tags.
8. **RouteToVault** – now integrated with VaultRoutingRuleService (ENG-02); ensures fragments land in appropriate vault/project.
9. **EmbedFragmentAction + EmbedFragment Job** – handles embeddings with toggle (ENG-04) and dedupe by content hash.
10. **Downstream** – fragments exposed via search/retrieval; model metadata stored on fragments for transparency.

## Key AI Touchpoints
- Chaos parsing prompt (no system role; raw instructions with JSON array format).
- Enrichment prompt expecting structured JSON; uses selected model (OpenAI GPT-4, Ollama, etc.).
- Type inference prompt with list of available types; uses ModelSelectionService + AIProviderManager.
- Tagging currently heuristic; opportunity for AI-assisted classification.

## Known Issues & Opportunities
- Prompts rely on plain text instructions; potential for system prompts to enforce JSON compliance and deterministic behaviour.
- Temperature/parameters not consistently defined (some defaults may remain high on provider side).
- Type inference confidence threshold static; lacks model-specific calibration.
- Metadata extraction duplicates work between ParseAtomicFragment and ExtractMetadataEntities; may need consolidation.
- Logging lacks correlation IDs linking a fragment through pipeline; error handling inconsistent.
- Tests limited around AI flows; minimal fixture coverage.

## References
- `app/Actions/*` mentioned above.
- `app/Services/AI/ModelSelectionService.php`, `TypeInferenceService.php`.
- `config/fragments.php`, `config/services.php`.
- Documentation to update (docs/overview, etc.).

## Desired Outcomes
- Clear map of pipeline stages (diagram + narrative).
- Recommendations for prompt structure, deterministic controls, and model assignments per classifier.
- Actionable backlog items for improving tagging, metadata, and logging.
- Alignment with AI-01 provider abstraction for future implementation.
