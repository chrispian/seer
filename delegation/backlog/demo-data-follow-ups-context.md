# Demo Data Follow-Ups — Context

Status: pending triage (2025-10-05)
Owner: Demo Dataset Team

## Overview

During validation of the new `DemoDataSeeder`, two areas surfaced that require follow-up work to keep demo environments clean and accurately linked:

1. **Vault duplication** between `DemoRoutingDataSeeder` and the new demo vault provisioning.
2. **Chat message linkage model** that currently relies on fragment metadata rather than formal relationships.

This document captures the problem statements, desired outcomes, and known constraints so follow-on tasks can be scoped quickly.

## Current State

- `Database\Seeders\DemoRoutingDataSeeder` creates plain `work`, `personal`, and `clients` vault records with minimal metadata.
- `Database\Seeders\Demo\Seeders\VaultSeeder` now provisions richer “Demo Work Vault” and “Demo Personal Vault” entries with demo-specific metadata. The earlier routing vaults remain, producing six total vaults in a fresh seed.
- `ChatSeeder` writes messages in two places:
  - As embedded arrays on each `chat_sessions.messages` column (existing behavior)
  - As discrete `Fragment` records tagged with `metadata.demo_category = chat_message` and `metadata.chat_session_id`
- No pivot/relationship tables exist to associate `Fragment` chat messages to `ChatSession` rows; downstream code still expects embedded messages.

## Desired Outcomes

1. **Vault Consolidation**
   - Single source of truth for demo vault definitions, avoiding duplicate entries or conflicting defaults.
   - Ensure dependent seeders (projects, routing rules, todos) still resolve vault references deterministically.
   - Provide migration/cleanup guidance for environments that already contain the duplicated vaults.

2. **Chat Message Relationship Model**
   - Establish a first-class relation between chat sessions and message fragments (e.g., `chat_session_id` FK on fragments or a dedicated pivot table).
   - Update seeders and runtime services to utilize that relation while keeping backward compatibility where necessary.
   - Document transition steps (schema updates, data migration, API adjustments).

## Open Questions

- Should routing demo vaults be replaced entirely by the richer demo vaults, or should routing data reference the new vault IDs instead?
- Is it preferable to retain embedded session messages for legacy flows while introducing relational storage, or is a full migration acceptable?
- Do any existing UI components assume vault slugs (`work`, `personal`) versus display names (`Demo Work Vault`), and how should those be normalized?
- What telemetry or indexing implications arise from moving chat messages into fragments (search, embeddings, timeline scopes)?

## Suggested Next Steps

1. **Vault Alignment Task**
   - Audit all references to routing vault slugs/IDs.
   - Decide on canonical vault records (rename vs. merge vs. soft-delete strategy).
   - Implement seeder updates and provide migration/cleanup script for existing data.

2. **Chat Message Relation Task**
   - Design schema change (fragment column, pivot table, or dedicated model).
   - Update `ChatSeeder` and runtime message creation flows to persist via the new relation.
   - Backfill existing chat sessions to guarantee parity between embedded arrays and fragment records (or deprecate one representation).

3. **Documentation & Export Prep**
   - Extend `demo-data-seeding-runbook` once decisions land.
   - Outline export strategy (SQL fixtures or JSON pack) once data structures stabilize.

## Dependencies & Risks

- Schema changes will impact `pgsql` (current local target) and any production database the demo dataset might ship to; migration design must account for both.
- Any changes to vault records risk breaking user credentials or routing rules if IDs/timestamps shift; must plan non-destructive updates.
- Chat relationship refactor may touch ingestion pipelines, embeddings jobs, and UI components that assume inlined messages.

## References

- Seeder sources: `database/seeders/Demo/`
- Validation artifacts: `delegation/backlog/demo-data-seeding-runbook.md`
- Latest run snapshot (2025-10-05) created 175 fragments (100 todo, 25 contact, 50 chat_message) across 10 chat sessions.
