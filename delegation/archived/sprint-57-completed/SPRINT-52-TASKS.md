# SPRINT-52 TASK LIST

**Sprint**: Obsidian Vault Import Integration  
**Status**: ✅ COMPLETE  
**Total Tasks**: 15/15 (100%)  
**Estimated Effort**: 2-3 days  
**Actual Effort**: 1 day  
**Completion Date**: 2025-01-06

## Summary
All tasks completed successfully. Production-ready with 893-file vault tested.

### Final Enhancements Delivered
- **Title extraction**: Front matter `title:` → Filename → H1 → First line (user feedback)
- **Force flag**: Added `--force` to bypass mtime check for re-imports after code changes
- **AI enrichment toggle**: Persistent UI setting with CLI override (`--enrich` flag)
- **Upsert logic**: No duplicates when re-importing (tracks by `metadata->obsidian_path`)
- **Smart sync**: Only processes new/modified files based on mtime
- **Daily scheduler**: Auto-sync at 03:00 UTC when enabled

### Test Results
- **Unit tests**: 11 passed (ObsidianMarkdownParser)
- **Feature tests**: 7 passed (ObsidianSyncCommand)
- **Total**: 18 tests, 46 assertions ✅

See `SPRINT-52-FINAL-SUMMARY.md` for complete implementation details.

---

## Phase 1: Core Infrastructure

### T-OBS-01: Create migration to seed `obsidian` source
**Priority**: High  
**Effort**: 15 min  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create database migration to add `obsidian` source to the `sources` table.

**Acceptance Criteria**:
- [ ] Migration file created: `2025_01_XX_seed_obsidian_source.php`
- [ ] Inserts source with key `obsidian`, label `Obsidian`
- [ ] Follows pattern from `2025_10_07_000100_seed_readwise_source.php`
- [ ] Down() method removes the source
- [ ] Migration runs successfully

**Files**:
- `database/migrations/2025_01_XX_seed_obsidian_source.php`

---

### T-OBS-02: Create `ObsidianMarkdownParser` service
**Priority**: High  
**Effort**: 2 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create service to parse Obsidian markdown files with YAML front matter.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/ObsidianMarkdownParser.php`
- [ ] Parses YAML front matter using Symfony YAML
- [ ] Extracts title from H1 or front matter `title:` field
- [ ] Strips internal links `[[...]]` for MVP
- [ ] Returns DTO with: title, body, frontMatter, tags
- [ ] Handles files without front matter gracefully
- [ ] Handles malformed YAML gracefully

**Files**:
- `app/Services/Obsidian/ObsidianMarkdownParser.php`
- `app/DTOs/ParsedObsidianNote.php` (new DTO)

**Technical Notes**:
```php
use Symfony\Component\Yaml\Yaml;

// Front matter pattern: ---\nYAML\n---
// Extract H1: /^#\s+(.+)$/m
// Strip wikilinks: preg_replace('/\[\[([^\]]+)\]\]/', '$1', $content)
```

---

### T-OBS-03: Create `ObsidianImportService` with sync logic
**Priority**: High  
**Effort**: 3 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create main service to orchestrate Obsidian vault imports.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/ObsidianImportService.php`
- [ ] Constructor injects `ObsidianMarkdownParser`, `DatabaseManager`
- [ ] `import(vaultPath, dryRun, enrich)` method scans vault directory
- [ ] Processes only `.md` files (recursive scan)
- [ ] Tracks file path and modified timestamp
- [ ] Creates/updates fragments by `metadata->obsidian_path`
- [ ] Maps folder names to tags (normalized)
- [ ] Maps front matter to tags/metadata
- [ ] Sets `source_key = obsidian`, `type = note`, `vault = codex`
- [ ] Returns stats: files_total, files_imported, files_updated, files_skipped
- [ ] Handles file read errors gracefully (log + skip)

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php`

**Technical Notes**:
- Use `Storage::disk('local')` or direct filesystem access
- Track sync state in user settings: `integrations.obsidian.last_synced_at`
- Update detection: compare `filemtime()` vs `metadata->obsidian_modified_at`

---

### T-OBS-04: Create `ObsidianSyncCommand` CLI
**Priority**: High  
**Effort**: 1 hour  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create Artisan command for manual Obsidian sync.

**Acceptance Criteria**:
- [ ] Command created: `app/Console/Commands/ObsidianSyncCommand.php`
- [ ] Signature: `obsidian:sync {--dry-run} {--enrich} {--vault-path=}`
- [ ] Description: "Sync Obsidian vault notes into Fragments Engine"
- [ ] Reads vault path from settings if not provided via option
- [ ] Validates vault path exists and is readable
- [ ] Calls `ObsidianImportService::import()`
- [ ] Displays summary table with stats
- [ ] Shows warning for dry-run mode
- [ ] Returns exit code 0 on success, 1 on failure

**Files**:
- `app/Console/Commands/ObsidianSyncCommand.php`

**Reference**: `app/Console/Commands/ReadwiseSyncCommand.php`

---

### T-OBS-05: Ensure `codex` vault exists
**Priority**: Medium  
**Effort**: 30 min  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create migration or seeder to ensure `codex` vault exists in database.

**Acceptance Criteria**:
- [ ] Migration/seeder creates `codex` vault if not exists
- [ ] Vault properties: name="codex", description="Imported notes and knowledge base"
- [ ] `is_default = false`, `sort_order = 99`
- [ ] Creates root project for codex vault if needed
- [ ] Idempotent (safe to run multiple times)

**Files**:
- `database/migrations/2025_01_XX_ensure_codex_vault_exists.php` OR
- `database/seeders/CodexVaultSeeder.php`

**Alternative**: Handle in `ObsidianImportService` on first run

---

### T-OBS-06: Update `SettingsController` for Obsidian integration
**Priority**: High  
**Effort**: 1.5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add Obsidian integration to settings backend.

**Acceptance Criteria**:
- [ ] `index()` method exposes Obsidian settings (excluding sensitive paths)
- [ ] `updateIntegrations()` validates new fields:
  - `obsidian_vault_path` (nullable|string|max:500)
  - `obsidian_sync_enabled` (nullable|boolean)
- [ ] Stores settings in `profile_settings.integrations.obsidian`
- [ ] Validates vault path exists and is readable directory
- [ ] Returns success response with current settings
- [ ] Logs integration updates

**Files**:
- `app/Http/Controllers/SettingsController.php`

**Settings Schema**:
```json
{
  "integrations": {
    "obsidian": {
      "vault_path": "/path/to/vault",
      "sync_enabled": true,
      "last_synced_at": "2025-01-06T03:00:00Z",
      "file_count": 450
    }
  }
}
```

**Reference**: Readwise integration in same controller (lines 178-229)

---

## Phase 2: Settings UI

### T-OBS-07: Add Obsidian card to Settings → Integrations
**Priority**: Medium  
**Effort**: 2 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create React component for Obsidian integration settings.

**Acceptance Criteria**:
- [ ] New card in Settings Integrations page
- [ ] Fields: vault_path (text input), sync_enabled (toggle)
- [ ] Shows last_synced_at timestamp when available
- [ ] Shows file_count from last sync
- [ ] "Test Connection" button validates path
- [ ] Save button calls `updateIntegrations` API
- [ ] Displays success/error toast on save
- [ ] Follows design pattern from Readwise card

**Files**:
- `resources/js/components/settings/IntegrationsSettings.tsx` (or similar)

**Design Notes**:
- Icon: 📝 or use Obsidian logo if available
- Title: "Obsidian Vault Import"
- Description: "Import your Obsidian notes into the codex vault"

---

### T-OBS-08: Add API route for testing vault path validity
**Priority**: Low  
**Effort**: 30 min  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add endpoint to validate Obsidian vault path before saving.

**Acceptance Criteria**:
- [ ] Route: `POST /settings/integrations/obsidian/test-path`
- [ ] Accepts: `vault_path` parameter
- [ ] Validates path exists and is readable
- [ ] Counts `.md` files in directory
- [ ] Returns: `valid` (bool), `file_count` (int), `error` (string|null)
- [ ] Does not modify database

**Files**:
- `routes/web.php`
- `app/Http/Controllers/SettingsController.php` (new method `testObsidianPath`)

**Response Example**:
```json
{
  "valid": true,
  "file_count": 450,
  "sample_files": ["Daily Notes/2025-01-06.md", "Projects/Fragments.md"]
}
```

---

## Phase 3: Scheduling & Pipeline

### T-OBS-09: Add daily scheduler entry
**Priority**: Medium  
**Effort**: 20 min  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add scheduled task to run Obsidian sync daily.

**Acceptance Criteria**:
- [ ] Scheduler entry in `routes/console.php`
- [ ] Runs `obsidian:sync` daily at 03:00 UTC
- [ ] Conditional: only runs when vault_path set and sync_enabled = true
- [ ] Uses `when()` callback to check user settings
- [ ] Logs success/failure
- [ ] Different time than Readwise (avoid conflicts)

**Files**:
- `routes/console.php`

**Reference**: Readwise scheduler (lines 52-66)

---

### T-OBS-10: Implement deterministic pipeline
**Priority**: High  
**Effort**: 1 hour  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Implement default import pipeline without AI processing.

**Acceptance Criteria**:
- [ ] Pipeline steps run in `ObsidianImportService`
- [ ] Steps: parse → extract title → map folders → map front matter → save
- [ ] No AI calls in deterministic mode
- [ ] All tags normalized (lowercase, slugified)
- [ ] Automatically adds `obsidian` tag to all imports
- [ ] Fast execution (< 100ms per file)

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php` (update)

---

### T-OBS-11: Implement `--enrich` flag with AI pipeline
**Priority**: Low  
**Effort**: 2 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add optional AI enrichment steps when `--enrich` flag is used.

**Acceptance Criteria**:
- [ ] `--enrich` flag triggers AI pipeline
- [ ] AI steps: type inference, entity extraction, auto-summary
- [ ] Uses existing AI services (`InferFragmentType`, entity extraction)
- [ ] Falls back gracefully if AI unavailable
- [ ] Significantly slower (5-10s per file)
- [ ] Logs enrichment progress

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php` (update)

**Dependencies**: Existing AI services from fragment processing

---

## Phase 4: Quality & Documentation

### T-OBS-12: Unit tests for `ObsidianMarkdownParser`
**Priority**: Medium  
**Effort**: 1.5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create comprehensive unit tests for markdown parser.

**Acceptance Criteria**:
- [ ] Test file: `tests/Unit/Services/ObsidianMarkdownParserTest.php`
- [ ] Test cases:
  - Parses valid YAML front matter
  - Extracts title from H1
  - Extracts title from front matter
  - Handles missing front matter
  - Handles malformed YAML gracefully
  - Strips wikilinks from body
  - Extracts tags from front matter
  - Handles empty files
- [ ] All tests pass
- [ ] 90%+ code coverage for parser

**Files**:
- `tests/Unit/Services/ObsidianMarkdownParserTest.php`

---

### T-OBS-13: Feature tests for `ObsidianSyncCommand`
**Priority**: Medium  
**Effort**: 2 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create feature tests for CLI command.

**Acceptance Criteria**:
- [ ] Test file: `tests/Feature/Console/ObsidianSyncCommandTest.php`
- [ ] Test cases:
  - Command runs in dry-run mode
  - Command imports markdown files
  - Command respects vault path from settings
  - Command validates vault path
  - Command shows error for invalid path
  - Command displays summary stats
  - Enrich flag triggers AI pipeline
- [ ] Uses temp directory with fixture files
- [ ] All tests pass

**Files**:
- `tests/Feature/Console/ObsidianSyncCommandTest.php`
- `tests/Fixtures/obsidian-vault/` (sample .md files)

**Reference**: `tests/Feature/Console/ReadwiseSyncCommandTest.php`

---

### T-OBS-14: Integration test for full sync workflow
**Priority**: Medium  
**Effort**: 1.5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create end-to-end integration test for complete sync workflow.

**Acceptance Criteria**:
- [ ] Test file: `tests/Feature/ObsidianIntegrationTest.php`
- [ ] Test cases:
  - Full sync creates fragments from vault
  - Updates existing fragments when files modified
  - Skips unmodified files
  - Maps folders to tags correctly
  - Maps front matter to metadata
  - Stores files in codex vault
  - Tracks sync state in user settings
- [ ] Uses realistic fixture vault structure
- [ ] All tests pass

**Files**:
- `tests/Feature/ObsidianIntegrationTest.php`

---

### T-OBS-15: Create documentation
**Priority**: Medium  
**Effort**: 1 hour  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create comprehensive documentation for Obsidian import feature.

**Acceptance Criteria**:
- [ ] Documentation file: `docs/ingestion/obsidian-import.md`
- [ ] Sections:
  - Overview
  - Setup instructions
  - Manual sync commands
  - Fragment structure
  - Front matter mapping
  - Folder to tag mapping
  - Scheduling
  - Troubleshooting
  - Future enhancements
- [ ] Includes examples
- [ ] Follows format from `readwise-import.md` and `chatgpt-import.md`

**Files**:
- `docs/ingestion/obsidian-import.md`

**Reference**: `docs/ingestion/readwise-import.md`

---

## Summary

| Phase | Tasks | Effort |
|-------|-------|--------|
| Phase 1: Core Infrastructure | 6 | 1 day |
| Phase 2: Settings UI | 2 | 0.5 day |
| Phase 3: Scheduling & Pipeline | 3 | 0.5 day |
| Phase 4: Quality & Documentation | 4 | 0.5 day |
| **Total** | **15** | **2-3 days** |

---

## Next Steps

1. Review and approve task breakdown
2. Assign tasks to sprint
3. Begin implementation with T-OBS-01 (source migration)
4. Progress through phases sequentially
5. Run full test suite after each phase
6. Create pull request with all changes
7. Mark sprint complete

**Ready to start?** 🚀
