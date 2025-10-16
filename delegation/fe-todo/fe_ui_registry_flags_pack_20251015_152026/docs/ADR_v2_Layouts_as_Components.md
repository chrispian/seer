# ADR-v2: Layouts-as-Components; Pages Separate

Status: Accepted
Date: 2025-10-15
Owner: PM Orchestrator

## Context
We want uniform rendering trees and minimal surface in the renderer, while preserving routing/auth/meta semantics.

## Decision
- **Layouts are Components** (`kind=layout`) in `fe_ui_components`.
- **Pages remain separate** in `fe_ui_pages` due to routing/meta/guards.
- Introduce **fe_ui_registry** for discovery/versioning of UI artifacts.
- Introduce **fe_ui_feature_flags** for experiments (e.g., holiday theming).

## Consequences
- Simpler renderer (one component tree).
- Clear governance for pages.
- Registry enables deterministic installs & version pinning.
- Flags enable safe experiments.

## Alternatives
- Separate layouts table (rejected: more complexity, little gain).

## Implementation Notes
- New migrations for registry/flags; alter components to add `kind`.
- FeatureFlagService evaluates flags by percentage and conditions.
