# Sprint 67: Obsidian Advanced Features (Option C - Phases 0-2)

**Sprint Code**: `SPRINT-67`  
**Status**: `planned`  
**Duration**: 6-7 days  
**Priority**: P0-P2 (Critical to High)

## Overview

Extend Obsidian integration with foundational improvements focusing on:
- **Phase 0 (P0)**: Deterministic Pipeline Enhancement - intelligent type inference without AI
- **Phase 1 (P1)**: Internal Links - wikilink resolution for knowledge graph
- **Phase 2 (P2)**: Bidirectional Sync - two-way sync with conflict detection

This sprint covers only Phases 0-2 (12 tasks) for immediate value delivery.

## Goals

1. **Deterministic Pipeline (P0)**: Enable intelligent type/tag inference from paths, front matter, and content patterns
2. **Internal Links (P1)**: Parse and resolve Obsidian wikilinks to create fragment relationships
3. **Bidirectional Sync (P2)**: Support writing fragments back to Obsidian with conflict detection

## Tasks

### Phase 0: Deterministic Pipeline (P0) - 2 tasks
- T-OBS-15.5: Create ObsidianFragmentPipeline service (4-5h)
- T-OBS-15.6: Enhance ObsidianImportService with pipeline (2-3h)

### Phase 1: Internal Links (P1) - 5 tasks
- T-OBS-16: Create WikilinkParser service (3-4h)
- T-OBS-17: Create LinkResolver service (4-5h)
- T-OBS-18: Enhance ObsidianMarkdownParser for link extraction (2-3h)
- T-OBS-19: Enhance ObsidianImportService for link resolution (4-5h)
- T-OBS-20: Add link visualization to fragment UI (3-4h)

### Phase 2: Bidirectional Sync (P2) - 5 tasks
- T-OBS-21: Create ObsidianWriteService (5-6h)
- T-OBS-22: Create ConflictDetector service (3-4h)
- T-OBS-23: Enhance ObsidianSyncCommand for bidirectional sync (4-5h)
- T-OBS-24: Add sync direction settings (UI + backend) (3-4h)
- T-OBS-25: Add conflict resolution logging and reporting (2-3h)

## Success Criteria

- All 12 tasks completed and tested
- Pipeline correctly infers types from paths and front matter
- Wikilinks resolved to fragment relationships
- Bidirectional sync working with conflict detection
- All existing functionality preserved (backwards compatible)

## Dependencies

- SPRINT-52 (Obsidian Vault Import Integration) ✅ COMPLETE
- `fragment_links` table (existing)
- Settings infrastructure for new configuration options

## Notes

This is Option C from the full SPRINT-67-TASKS.md specification, focusing on P0-P2 priorities only. Phases 3-6 (nested folders, multiple vaults, media, testing) deferred to future sprints.
