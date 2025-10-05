# Plan — v0.0.1 (MVP Backend)

## Goals
- File-based Command Packs + Registry Cache + DSL Runner.
- Built-in packs: `/todo`, `/note`, `/link`, `/recall`, `/search`.
- No UI; CLI-only management and execution via the existing chat pipeline.

## Tasks
1. **DB Migration: `command_registry`**
   - Columns per `01-commands-architecture.md`.
2. **Registry Loader**
   - Discover packs, validate manifests, compute `steps_hash`, upsert cache.
3. **DSL Engine**
   - Templating + filters; step dispatcher; typed outputs; error surfaces.
4. **Step Implementations**
   - `transform`, `ai.generate` (flaggable), `fragment.create` (validates Type Packs), `search.query`, `notify`, `tool.call`.
5. **Artisan Commands**
   - `frag:command:make`, `frag:command:cache`, `frag:command:test`.
6. **Built-in Command Packs**
   - Provide five packs in `fragments/commands/*`.
7. **Docs & Samples**
   - Add `samples/` inputs + golden outputs for each pack.
8. **Security & Limits**
   - Capability list check; per-step timeouts; max tokens for `ai.generate`.

## Exit Criteria
- All built-in commands execute end-to-end in dry and live modes.
- Registry cache rebuild detects/prints overrides.
- Type Pack validation runs on `fragment.create`.
