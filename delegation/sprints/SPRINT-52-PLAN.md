# SPRINT-52: Obsidian Vault Import Integration

**Status**: ✅ COMPLETE  
**Sprint Goal**: Import Obsidian vault notes into Fragments Engine with daily sync  
**Estimated Effort**: 2-3 days  
**Actual Effort**: 1 day  
**Priority**: Medium  
**Dependencies**: None (reuses existing integration patterns from Readwise)  
**Completion Date**: 2025-01-06

> **See**: `SPRINT-52-FINAL-SUMMARY.md` for complete implementation details, test results, and production usage.

---

## Overview

Add Obsidian vault import functionality that allows users to sync their markdown notes from an Obsidian vault into Fragments Engine. Notes will be stored in a dedicated "codex" vault, with front matter mapped to tags and folder structure flattened with folder names as tags.

### Scope Definition

**In Scope:**
- Basic markdown file import from Obsidian vault
- Front matter parsing (YAML) → tags/metadata
- Folder structure → tags (no nested folder support)
- Daily scheduled sync
- Settings UI integration (similar to Readwise)
- Custom deterministic pipeline (no AI by default)
- Optional `--enrich` flag for AI enrichment
- Source tracking: `source_key = obsidian`
- Type: `note`

**Out of Scope (Future Versions):**
- Internal link resolution (`[[wikilinks]]`)
- Media/attachment imports (images, PDFs, etc.)
- Bidirectional sync (Fragments → Obsidian write-back)
- AI merge conflict resolution for dual-edited files
- Multiple vault paths
- One-time sync option
- Nested folder hierarchy preservation

---

## Architecture Design

### Components

1. **ObsidianImportService** (`app/Services/Obsidian/ObsidianImportService.php`)
   - Main orchestrator for import logic
   - Reads vault directory structure
   - Processes markdown files
   - Creates/updates fragments
   - Tracks sync state (last modified times)

2. **ObsidianMarkdownParser** (`app/Services/Obsidian/ObsidianMarkdownParser.php`)
   - Parses YAML front matter
   - Extracts body content
   - Strips/ignores internal links for MVP
   - Returns structured DTO

3. **ObsidianSyncCommand** (`app/Console/Commands/ObsidianSyncCommand.php`)
   - CLI interface: `php artisan obsidian:sync`
   - Options: `--dry-run`, `--enrich`, `--vault-path=`
   - Delegates to ObsidianImportService

4. **Settings Integration** (`app/Http/Controllers/SettingsController.php`)
   - Add `obsidian` to integrations settings
   - Store: vault_path, sync_enabled, last_synced_at
   - UI similar to Readwise card

5. **Scheduled Job** (`routes/console.php`)
   - Daily sync at 03:00 UTC (different time than Readwise)
   - Conditional: only runs when vault_path set and sync_enabled = true

6. **Source Record** (Migration)
   - Seed `sources` table with `obsidian` key
   - Migration: `2025_01_XX_seed_obsidian_source.php`

### Data Flow

```
Obsidian Vault (filesystem)
    ↓
ObsidianMarkdownParser → ParsedNote DTO
    ↓
ObsidianImportService → Fragment records
    ↓
[Optional] AI Enrichment Pipeline
    ↓
Database (fragments table, vault = 'codex')
```

### Fragment Structure

Each Obsidian note becomes a Fragment:

```php
[
    'message' => '# Note Title\n\nBody content...',
    'title' => 'Note Title', // from H1 or front matter title
    'type' => 'note',
    'source_key' => 'obsidian',
    'vault' => 'codex', // vault name (ensure 'codex' vault exists)
    'project_id' => null, // or root project ID
    'tags' => ['folder-name', 'frontmatter-tag1', 'frontmatter-tag2', 'obsidian'],
    'metadata' => [
        'obsidian_path' => 'Daily Notes/2025-01-06.md',
        'obsidian_modified_at' => '2025-01-06T12:34:56Z',
        'front_matter' => [...], // full YAML front matter
    ],
]
```

### Sync Strategy (MVP)

- **One-way sync**: Obsidian → Fragments Engine
- **File tracking**: Store file path + modified timestamp in metadata
- **Update detection**: Compare filesystem mtime vs stored modified_at
- **New files**: Create new fragment
- **Modified files**: Update existing fragment (by obsidian_path)
- **Deleted files**: Ignored in MVP (manual cleanup)

### Pipeline Design

**Deterministic Pipeline (Default):**
1. Parse markdown + front matter
2. Extract title (H1 or front matter)
3. Map folder → tag
4. Map front matter → tags/metadata
5. Create/update fragment

**Enrichment Pipeline (--enrich flag):**
1. All deterministic steps
2. AI type inference (could be todo, meeting note, etc.)
3. Entity extraction
4. Auto-summary generation
5. Relationship detection

---

## Settings Schema

Add to `users.profile_settings` JSON:

```json
{
  "integrations": {
    "obsidian": {
      "vault_path": "/Users/chrispian/Documents/ObsidianVault",
      "sync_enabled": true,
      "last_synced_at": "2025-01-06T03:00:00Z",
      "file_count": 450,
      "last_import_stats": {
        "files_total": 450,
        "files_imported": 12,
        "files_updated": 3,
        "files_skipped": 435
      }
    }
  }
}
```

---

## Task Breakdown

### Phase 1: Core Infrastructure (6 tasks)
- **T-OBS-01**: Create migration to seed `obsidian` source
- **T-OBS-02**: Create `ObsidianMarkdownParser` service
- **T-OBS-03**: Create `ObsidianImportService` with sync logic
- **T-OBS-04**: Create `ObsidianSyncCommand` CLI
- **T-OBS-05**: Ensure `codex` vault exists (migration or seed)
- **T-OBS-06**: Update `SettingsController` for Obsidian integration

### Phase 2: Settings UI (2 tasks)
- **T-OBS-07**: Add Obsidian card to Settings → Integrations (React)
- **T-OBS-08**: Add API route for testing vault path validity

### Phase 3: Scheduling & Pipeline (3 tasks)
- **T-OBS-09**: Add daily scheduler entry in `routes/console.php`
- **T-OBS-10**: Implement deterministic pipeline
- **T-OBS-11**: Implement `--enrich` flag with AI pipeline steps

### Phase 4: Quality & Documentation (4 tasks)
- **T-OBS-12**: Unit tests for `ObsidianMarkdownParser`
- **T-OBS-13**: Feature tests for `ObsidianSyncCommand`
- **T-OBS-14**: Integration test for full sync workflow
- **T-OBS-15**: Create documentation (`docs/ingestion/obsidian-import.md`)

**Total: 15 tasks**

---

## Success Criteria

- [ ] User can configure Obsidian vault path in Settings → Integrations
- [ ] CLI command `php artisan obsidian:sync` successfully imports notes
- [ ] Front matter YAML parsed and mapped to tags/metadata
- [ ] Folder names converted to tags
- [ ] Notes stored in `codex` vault with `source_key = obsidian`
- [ ] Daily sync runs automatically when enabled
- [ ] `--dry-run` shows preview without writing to DB
- [ ] `--enrich` flag triggers AI pipeline steps
- [ ] All tests pass (90%+ coverage)
- [ ] Documentation complete

---

## Future Enhancements (Post-MVP)

### Version 2: Internal Links
- Parse `[[wikilinks]]` and create `fragment_links` records
- Support `[[note#heading]]` anchor links
- Handle aliases `[[note|display text]]`

### Version 3: Bidirectional Sync
- Make Fragments Engine source of truth
- Write changes back to .md files
- AI merge for dual-edited files
- Conflict resolution UI

### Version 4: Advanced Features
- Media imports (images, PDFs)
- Multiple vault support
- One-time import option
- Nested folder hierarchy (parent-child tags)
- Dataview query support
- Template variable expansion

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Vault path invalid/unreadable | High | Validate path on save, show clear error messages |
| Large vaults (1000+ files) timeout | Medium | Process in batches, add progress logging |
| YAML parsing errors | Medium | Graceful fallback, log errors, continue import |
| Duplicate file names in different folders | Low | Use full path as unique key in metadata |
| Permission issues reading files | Medium | Check readable before import, skip inaccessible files |

---

## Technical Notes

### Front Matter Parsing

Use Symfony YAML component (already in Laravel):

```php
use Symfony\Component\Yaml\Yaml;

$frontMatterMatch = preg_match('/^---\n(.*?)\n---\n/s', $content, $matches);
if ($frontMatterMatch) {
    $frontMatter = Yaml::parse($matches[1]);
    $body = substr($content, strlen($matches[0]));
}
```

### Vault/Project Setup

Ensure `codex` vault exists:

```php
$codexVault = Vault::firstOrCreate(
    ['name' => 'codex'],
    [
        'description' => 'Imported notes and knowledge base',
        'is_default' => false,
        'sort_order' => 99,
    ]
);

// Use root project or create "Obsidian" project
$rootProject = Project::forVault($codexVault->id)
    ->where('name', 'Root')
    ->first();
```

### Tag Normalization

```php
// Folder: "Daily Notes" → tag: "daily-notes"
// Front matter tag: "Meeting Notes" → "meeting-notes"
$tag = Str::slug($rawTag);
```

---

## Estimated Timeline

- **Phase 1 (Core)**: 1 day
- **Phase 2 (UI)**: 0.5 days
- **Phase 3 (Pipeline)**: 0.5 days
- **Phase 4 (QA)**: 0.5 days
- **Buffer**: 0.5 days

**Total: 2-3 days**

---

## Dependencies

- Symfony YAML component (already in Laravel)
- Existing Vault and Project models
- Existing Fragment model and inbox system
- Settings infrastructure (from Readwise integration)

---

## Review & Sign-off

- [ ] Architecture reviewed
- [ ] Task breakdown approved
- [ ] Sprint ready to start

**Created**: 2025-01-06  
**Author**: AI Assistant  
**Reviewer**: @chrispian
