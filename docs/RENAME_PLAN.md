# Rename & Extraction Plan

The legacy `seer` repository is being split into two packages:

- `hollis-labs/fragments-engine` – reusable ingestion + recall engine (Laravel package).
- `hollis-labs/project-mentat` – NativePHP desktop shell that consumes the engine.

## Current Status (2025-09-19)
- Fresh Laravel 12 skeletons created for both packages with SQLite defaults.
- Namespaces updated to `HollisLabs\FragmentsEngine` and `HollisLabs\ProjectMentat`.
- Fragments Engine now houses the shared domain codebase; Project Mentat consumes it via a local Composer path repository.
- Readmes and changelogs seeded with `0.2.0` prerelease notes.

## Next Steps
1. Add automated test coverage + docs for the new `FragmentsEngineServiceProvider` surface area.
2. Tag the engine and swap Project Mentat from path repository to versioned dependency.
3. Update issue trackers and CI pipelines to point at the new repositories.
4. Sunset the legacy `seer` app after confirming feature parity.

Keep progress synced with `PROJECT_PLAN.md` until GitHub issues are opened in the new repos.
