# DSL Custom Commands Review

## Overview
- Break down the recommendations for evolving the DSL-based slash command system into actionable sprints/tasks.
- Prioritise steps that move us toward deterministic, user-configurable command flows that can be authored via a UI builder.

## Findings & Suggestions
- **Deterministic Coverage**
  - Replace AI-dependent parsing in `/todo` with structured input + validation steps; prototype a regex or rule-based parser that lives entirely in DSL.
  - Identify other commands using `ai.generate` (e.g., news digest) and plan deterministic alternatives or optional fallbacks.
  - Add utility steps (`context.merge`, `list.map`, `string.format`) so authors can manipulate data without calling AI.
  - Extend command registry metadata with capability flags (e.g., deterministic-only) for UI filtering and policy enforcement.

- **Flow Builder Enablement**
  - Emit machine-readable step schemas mirroring `StepFactory` so the TS builder can render configuration forms and validations.
  - Decide on storage (filesystem vs DB) for user-authored command packs; ensure loader precedence and caching continue to work.
  - Leverage existing `dryRun` support for in-editor previews and validation before publishing commands.

- **Happy/Error Path UX**
  - Introduce `on_error` handling so command authors can choose fallback steps instead of relying on CommandRunner short-circuit behaviour.
  - Enhance `notify` (and related response steps) to expose explicit UX targets (`toast`, `modal`, `silent`) and feed them through `CommandController::convertDslResultToResponse`.
  - Record per-step errors in execution context to enable downstream catch/branch logic without PHP intervention.
  - Establish command-level defaults (always log, customizable user-facing message) to guarantee consistent error observability.

- **Next Actions for Planning**
  - Produce TS interface definitions and step metadata exports.
  - Draft migration plan for deterministic `/todo` and document best-practice patterns around new database steps.
  - Create implementation tickets for error-path customization and registry metadata enhancements.
