# Settings Experience Audit

## Summary
- Document the current `/settings` experience, identify editable settings, confirm UI coverage, surface planned work, and propose follow-on tasks for gaps.

## Overview
- Review Laravel + React settings implementation exposed via `/settings`.
- Map each stored preference to its existing UI affordance or missing interface.
- Cross-reference delegation plans to avoid duplicate roadmapping.
- Recommend backlog-ready tasks for expanding settings coverage and UX polish.

## Findings & Suggestions

- **Profile & Identity**
  - `/settings/profile` updates `name`, `display_name`, and `email`; flows are complete and validated.
  - Avatar management supports gravatar toggling, uploads, and cache busting; align success feedback with other sections.

- **Preferences & Notifications**
  - Language, timezone, and notification booleans (`email`, `desktop`, `sound`) persist via `/settings/preferences`; lacks advanced routing and channel-specific controls.
  - `layout.sidebar_collapsed` already covered through in-app toggles/shortcuts, no additional UI needed.

- **Appearance & Layout**
  - Theme selector and layout width/compact toggles reuse the preferences endpoint; consider splitting submission/loading state so appearance actions don’t block profile forms.
  - Import, export, and reset controls appear, but only export is wired; import/reset need endpoints, client flows, and error handling.

- **AI Configuration**
  - Provider dropdown and model/parameter fields persist under `profile_settings.ai`; currently static options—should load dynamic metadata from `config/fragments.php` and validate API key prerequisites.
  - Context length, streaming, and auto-title toggles are functional but need guardrails against exceeding project-level limits.

- **Planned & Related Work**
  - Sprint 42 (`delegation/sprint-42/UX-03-05-settings-page`) scopes tab scaffolding, validation, import/export, reset, and accessibility.
  - Sprint 45 (`delegation/sprint-45/UX-06-*`) targets provider dashboards and advanced model settings that should fold into the AI tab.
  - Sprint 57 (`delegation/sprint-57/VECTOR-005-configuration-detection`) expects surfacing vector driver status in settings.

- **Recommended Next Steps**
  - Implement import + reset pipelines (backend endpoints, client dialogs, confirmation UX) to complete the settings management card.
  - Drive AI provider/model pickers from provider catalog config, surfacing capability/status badges and missing-key warnings.
  - Expand notification preferences into granular channels (digest emails, real-time alerts) with contextual copy.
  - Add admin-only panels for env-driven flags (embeddings enablement, tool allowlists, transparency toggles) with read-only state when locked.
  - Separate loading/success state per tab section to prevent global spinners and improve feedback clarity.
