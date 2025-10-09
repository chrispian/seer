# SPRINT-67 TASK LIST

**Sprint**: Obsidian Advanced Features - Deterministic Pipeline, Internal Links & Bi-Directional Sync  
**Status**: 📋 PLANNED  
**Total Tasks**: 25 tasks across 7 phases  
**Estimated Effort**: 10-16 days  
**Priority Breakdown**: P0 (2 tasks - START HERE), P1 (5 tasks), P2 (5 tasks), P3 (6 tasks), P4 (3 tasks), Testing (4 tasks)

## Summary

Extend Obsidian integration with advanced features: deterministic pipeline enhancement, internal link resolution, bidirectional sync, nested folders, multiple vaults, and media import. Can be executed as sub-sprints (67.0, 67a-67e) based on priority.

**Recommended Execution:**
1. **Sprint 67.0**: Deterministic Pipeline (P0) - 0.5-1 day 🔥 START HERE
2. **Sprint 67a**: Internal Links (P1) - 2-3 days
3. **Sprint 67b**: Bidirectional Sync (P2) - 2-3 days
4. **Sprint 67c**: Advanced Features (P3) - 2-3 days
5. **Sprint 67d**: Media Support (P4) - 1-2 days
6. **Sprint 67e**: Quality Assurance - 1-2 days

---

## Phase 0: Deterministic Pipeline Enhancement (P0) - 2 tasks 🔥 START HERE

### T-OBS-15.5: Create ObsidianFragmentPipeline service
**Priority**: P0 (Critical - do first!)  
**Effort**: 4-5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create deterministic pipeline for intelligent type inference, tagging, and metadata extraction based on paths, front matter, and content patterns WITHOUT AI.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/ObsidianFragmentPipeline.php`
- [ ] Path-based type inference (e.g., `Contacts/` → `contact`, `Meetings/` → `meeting`)
- [ ] Front matter `type:` field extraction (highest priority)
- [ ] Content pattern matching for types (checkbox lists → task, meeting headers → meeting)
- [ ] Folder-based tag generation (all parent folders become tags)
- [ ] Front matter tag extraction (array or comma-separated)
- [ ] Content hashtag extraction (#tag syntax)
- [ ] Custom front matter field extraction (author, date, project, priority, status, etc.)
- [ ] Default fallback to 'note' type
- [ ] Returns enriched fragment data DTO

**Files**:
- `app/Services/Obsidian/ObsidianFragmentPipeline.php`
- `app/DTOs/EnrichedObsidianFragment.php` (new)

**Technical Notes**:
```php
// Type inference priority:
// 1. Front matter 'type:' field (explicit)
// 2. Path-based rules (Contacts → contact, Meetings → meeting)
// 3. Content patterns (checkbox lists, meeting headers)
// 4. Default: 'note'

// Path rules to implement:
$pathRules = [
    'Contacts' => 'contact',
    'People' => 'contact',
    'Meetings' => 'meeting',
    'Meeting Notes' => 'meeting',
    'Tasks' => 'task',
    'TODO' => 'task',
    'Projects' => 'project',
    'Ideas' => 'idea',
    'References' => 'reference',
    'Clippings' => 'clip',
    'Bookmarks' => 'bookmark',
    'Daily Notes' => 'log',
    'Journal' => 'log',
];

// Content patterns:
$contentPatterns = [
    '/^#+ Meeting:/' => 'meeting',
    '/^- \[[ x]\]/' => 'task', // Checkbox syntax
    '/^## Action Items/' => 'meeting',
    '/^Project:/' => 'project',
];

// Custom metadata fields to extract from front matter:
$customFields = [
    'author', 'date', 'project', 'priority', 
    'status', 'category', 'url', 'source_url'
];
```

**Example Input/Output**:
```php
// Input file: Contacts/John Doe.md
---
type: contact
tags: [work, sales]
email: john@example.com
company: Acme Corp
---
# John Doe
Sales contact from Acme...

// Output:
[
    'type' => 'contact', // from front matter
    'tags' => ['contacts', 'work', 'sales', 'obsidian'], // folder + front matter + source
    'custom_metadata' => [
        'email' => 'john@example.com',
        'company' => 'Acme Corp',
    ],
]
```

**Testing Requirements**:
- [ ] Test path-based inference for all folder types
- [ ] Test front matter type override
- [ ] Test content pattern matching
- [ ] Test tag generation from multiple sources
- [ ] Test custom field extraction

---

### T-OBS-15.6: Enhance ObsidianImportService with pipeline integration
**Priority**: P0 (Critical)  
**Effort**: 2-3 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Integrate ObsidianFragmentPipeline into import flow, replacing simple type assignment with intelligent pipeline.

**Acceptance Criteria**:
- [ ] Inject `ObsidianFragmentPipeline` into `ObsidianImportService`
- [ ] Run pipeline after markdown parsing
- [ ] Use pipeline-inferred type instead of hardcoded 'note'
- [ ] Merge pipeline tags with existing tag logic
- [ ] Store custom metadata in fragment metadata field
- [ ] Pipeline runs before AI enrichment (deterministic first)
- [ ] AI enrichment can still override type if enabled
- [ ] Backwards compatible with existing imports
- [ ] Add pipeline stats to import summary

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php` (update)

**Implementation Changes**:
```php
// BEFORE (current - line 136):
$fragment->fill([
    'message' => $parsed->body,
    'title' => Str::limit($parsed->title, 255),
    'type' => 'note', // ALWAYS 'note'
    'source' => 'Obsidian',
    'source_key' => 'obsidian',
    'vault' => $codexVault->name,
    'project_id' => $rootProject->id,
]);

// AFTER (with pipeline):
// Run deterministic pipeline
$enriched = $this->pipeline->process($parsed, $relativePath, $folderName);

$fragment->fill([
    'message' => $parsed->body,
    'title' => Str::limit($parsed->title, 255),
    'type' => $enriched->type, // FROM PIPELINE
    'source' => 'Obsidian',
    'source_key' => 'obsidian',
    'vault' => $codexVault->name,
    'project_id' => $rootProject->id,
]);

// Merge tags from pipeline
$tags = array_unique(array_merge($enriched->tags, $tags));

// Store custom metadata
$fragment->metadata = array_merge($fragment->metadata ?? [], [
    'obsidian_path' => $relativePath,
    'obsidian_modified_at' => $fileModifiedAt->toIso8601String(),
    'front_matter' => $parsed->frontMatter,
    'custom_fields' => $enriched->customMetadata, // NEW
]);
```

**Stats Enhancement**:
```php
$stats = [
    'files_total' => 0,
    'files_imported' => 0,
    'files_updated' => 0,
    'files_skipped' => 0,
    'types_inferred' => [], // NEW: ['contact' => 5, 'meeting' => 3, ...]
    'tags_generated' => 0, // NEW
    'dry_run' => $dryRun,
    'enrich' => $enrich,
    'force' => $force,
];
```

**Testing Requirements**:
- [ ] Verify pipeline runs for all imports
- [ ] Verify type correctly inferred from paths
- [ ] Verify tags merged from pipeline + folders
- [ ] Verify custom metadata stored
- [ ] Verify AI enrichment still works when enabled

---

## Phase 1: Internal Links (P1) - 5 tasks

### T-OBS-16: Create WikilinkParser service
**Priority**: P1 (High)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create service to extract and parse Obsidian wikilink syntax from markdown content.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/WikilinkParser.php`
- [ ] Extracts `[[target]]` basic links
- [ ] Parses `[[target#heading]]` anchor syntax
- [ ] Parses `[[target|alias]]` display text syntax
- [ ] Returns structured array: `[target, heading, alias, position]`
- [ ] Handles nested brackets gracefully
- [ ] Handles multiple links per document
- [ ] Returns empty array if no links found

**Files**:
- `app/Services/Obsidian/WikilinkParser.php`

**Technical Notes**:
```php
// Link patterns
// Basic: [[Project Plan]]
// Anchor: [[Project Plan#Goals]]
// Alias: [[Project Plan|The Plan]]
// Combined: [[Project Plan#Goals|See Goals]]

// Regex pattern (simplified):
// /\[\[([^\]]+)\]\]/g

// Parse result structure:
[
    [
        'raw' => '[[Project Plan#Goals|See Goals]]',
        'target' => 'Project Plan',
        'heading' => 'Goals',
        'alias' => 'See Goals',
        'position' => 42,
    ],
]
```

---

### T-OBS-17: Create LinkResolver service
**Priority**: P1 (High)  
**Effort**: 4-5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create service to resolve wikilink targets to fragment IDs and create fragment_links records.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/LinkResolver.php`
- [ ] Builds filename→fragment_id lookup table from vault fragments
- [ ] Matches link targets to fragments (case-insensitive)
- [ ] Handles ambiguous targets (multiple matches) → logs warning, uses first match
- [ ] Tracks orphan links (no matching fragment) → returns orphan list
- [ ] Creates `fragment_links` records with obsidian metadata
- [ ] Returns resolution stats: total_links, resolved, orphaned, ambiguous

**Files**:
- `app/Services/Obsidian/LinkResolver.php`

**Technical Notes**:
```php
// Link resolution metadata
'metadata' => [
    'obsidian_link' => true,
    'link_text' => '[[Project Plan#Goals]]',
    'target_filename' => 'Project Plan.md',
    'anchor' => 'Goals',
    'alias' => null,
    'is_orphan' => false,
    'position' => 42,
    'link_type' => 'wikilink', // vs 'markdown_link'
]

// Performance optimization for large vaults
$filenameMap = Fragment::where('source_key', 'obsidian')
    ->where('vault', 'codex')
    ->get()
    ->keyBy(fn($f) => strtolower($this->extractFilename($f->metadata['obsidian_path'])));
```

**Reference**: Fragment link creation pattern from chat message linking

---

### T-OBS-18: Enhance ObsidianMarkdownParser for link extraction
**Priority**: P1 (High)  
**Effort**: 2-3 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Enhance existing parser to extract wikilinks instead of stripping them.

**Acceptance Criteria**:
- [ ] Inject `WikilinkParser` into `ObsidianMarkdownParser`
- [ ] Extract wikilinks during parsing (before body processing)
- [ ] Add `links` array to `ParsedObsidianNote` DTO
- [ ] Preserve original wikilink syntax in body (don't strip)
- [ ] Handle edge cases: links in code blocks, links in front matter
- [ ] Backwards compatible: existing imports still work

**Files**:
- `app/Services/Obsidian/ObsidianMarkdownParser.php` (update)
- `app/DTOs/ParsedObsidianNote.php` (update DTO)

**DTO Enhancement**:
```php
class ParsedObsidianNote extends DataTransferObject
{
    public function __construct(
        public string $title,
        public string $body,
        public array $tags,
        public array $frontMatter,
        public array $links = [], // NEW
    ) {}
}
```

---

### T-OBS-19: Enhance ObsidianImportService for link resolution
**Priority**: P1 (High)  
**Effort**: 4-5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add two-pass import: first pass imports fragments, second pass resolves links.

**Acceptance Criteria**:
- [ ] Inject `LinkResolver` into `ObsidianImportService`
- [ ] Store parsed links in `metadata->obsidian_links` during first pass
- [ ] After all files imported, run link resolution pass
- [ ] Create/update `fragment_links` for resolved links
- [ ] Log orphan links with file path and link target
- [ ] Add link resolution stats to import summary
- [ ] Handle incremental syncs: update links only for changed files

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php` (update)

**Import Flow**:
```
1. First pass: Import all files
   - Parse markdown + extract links
   - Create/update fragments
   - Store links in metadata->obsidian_links

2. Second pass: Resolve links
   - Build filename→fragment_id map
   - For each fragment with links:
     - Resolve link targets
     - Create fragment_links records
     - Update metadata with resolution status

3. Return enhanced stats:
   - files_imported, files_updated, files_skipped
   - links_total, links_resolved, links_orphaned
```

---

### T-OBS-20: Add link visualization to fragment UI
**Priority**: P1 (Medium)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Display linked fragments in fragment detail view.

**Acceptance Criteria**:
- [ ] Show "Linked Notes" section in fragment detail modal
- [ ] List outbound links (links from this fragment)
- [ ] List inbound links (links to this fragment)
- [ ] Click link to navigate to target fragment
- [ ] Show orphan links with warning icon
- [ ] Display anchor/alias if present
- [ ] Handle no links gracefully (hide section)

**Files**:
- `resources/js/components/FragmentDetail.tsx` (or similar)

**Design Notes**:
```
Linked Notes
─────────────
→ Outbound Links (3)
  • Project Plan #Goals (alias: "See Goals")
  • Meeting Notes
  • Roadmap ⚠️ (orphan - target not found)

← Inbound Links (2)
  • Daily Note 2025-01-05
  • Sprint Planning
```

---

## Phase 2: Bidirectional Sync (P2) - 5 tasks

### T-OBS-21: Create ObsidianWriteService
**Priority**: P2 (High)  
**Effort**: 5-6 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create service to write fragment changes back to Obsidian markdown files.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/ObsidianWriteService.php`
- [ ] Writes fragment message to `.md` file
- [ ] Preserves YAML front matter structure
- [ ] Updates front matter from fragment tags/metadata
- [ ] Handles file creation for new fragments (no obsidian_path)
- [ ] Updates file modification timestamp
- [ ] Validates path before writing (security)
- [ ] Logs all file writes with before/after snapshots

**Files**:
- `app/Services/Obsidian/ObsidianWriteService.php`

**Technical Notes**:
```php
// Write flow
1. Load existing file content (if exists)
2. Parse front matter
3. Update front matter with fragment data:
   - title: $fragment->title
   - tags: $fragment->tags (excluding 'obsidian')
   - updated_at: $fragment->updated_at
4. Combine front matter + fragment body
5. Write to file with atomic operation
6. Update fragment metadata:
   - obsidian_modified_at = current timestamp
```

**Safety**:
- Validate path is within vault directory (prevent path traversal)
- Backup file content before overwrite
- Use file locks during write

---

### T-OBS-22: Create ConflictDetector service
**Priority**: P2 (High)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Detect conflicts between filesystem and fragment changes.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/ConflictDetector.php`
- [ ] Compares file `mtime` vs fragment `updated_at`
- [ ] Detects three states: file_newer, fragment_newer, conflict
- [ ] Conflict = both changed since last sync
- [ ] Returns conflict report with resolution recommendation
- [ ] Logs conflicts to dedicated log file
- [ ] Strategy: last-write-wins (configurable)

**Files**:
- `app/Services/Obsidian/ConflictDetector.php`

**Conflict Detection Logic**:
```php
// State comparison
$lastSync = $settings['last_synced_at'] ?? null;
$fileModified = filemtime($filePath);
$fragmentModified = $fragment->updated_at->timestamp;

if ($fileModified > $lastSync && $fragmentModified > $lastSync) {
    return 'conflict'; // Both changed
} elseif ($fileModified > $fragmentModified) {
    return 'file_newer'; // Import
} elseif ($fragmentModified > $fileModified) {
    return 'fragment_newer'; // Export
} else {
    return 'in_sync'; // No changes
}
```

---

### T-OBS-23: Enhance ObsidianSyncCommand for bidirectional sync
**Priority**: P2 (High)  
**Effort**: 4-5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add bidirectional sync capabilities to CLI command.

**Acceptance Criteria**:
- [ ] Add `--direction` option: `import`, `export`, `bidirectional`
- [ ] Default direction from settings (fallback: `import`)
- [ ] Import mode: existing behavior (read files)
- [ ] Export mode: write fragment changes to files
- [ ] Bidirectional mode: import + export with conflict detection
- [ ] Show conflict resolution decisions in output
- [ ] Add `--dry-run` support for export operations
- [ ] Summary includes: files_imported, files_exported, conflicts_detected

**Files**:
- `app/Console/Commands/ObsidianSyncCommand.php` (update)

**Command Usage**:
```bash
php artisan obsidian:sync --direction=import       # Read-only (default)
php artisan obsidian:sync --direction=export       # Write-only
php artisan obsidian:sync --direction=bidirectional # Two-way sync
php artisan obsidian:sync --dry-run --direction=export  # Preview exports
```

---

### T-OBS-24: Add sync direction settings (UI + backend)
**Priority**: P2 (Medium)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add settings for bidirectional sync configuration.

**Acceptance Criteria**:
- [ ] Backend: Add `sync_direction` to settings validation
- [ ] Backend: Add `conflict_resolution` strategy setting
- [ ] UI: Add "Sync Direction" dropdown: One-way (Import) / Two-way (Sync)
- [ ] UI: Add conflict resolution strategy selector
- [ ] UI: Warning message for two-way sync (backup recommendation)
- [ ] Settings schema updated with new fields

**Files**:
- `app/Http/Controllers/SettingsController.php` (update)
- `resources/js/components/SettingsPage.tsx` (update)

**Settings Schema**:
```json
{
  "integrations": {
    "obsidian": {
      "vault_path": "/path/to/vault",
      "sync_enabled": true,
      "sync_direction": "import", // "import" | "export" | "bidirectional"
      "conflict_resolution": "last_write_wins", // "last_write_wins" | "manual"
      "enrich_enabled": false,
      "last_synced_at": "2025-01-06T12:00:00Z"
    }
  }
}
```

---

### T-OBS-25: Add conflict resolution logging and reporting
**Priority**: P2 (Medium)  
**Effort**: 2-3 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create comprehensive logging for conflict detection and resolution.

**Acceptance Criteria**:
- [ ] Log all conflicts to `storage/logs/obsidian-conflicts.log`
- [ ] Log format: timestamp, file path, resolution decision, checksums
- [ ] Add conflict summary to sync command output
- [ ] Store conflict history in settings metadata
- [ ] Create `/settings/integrations/obsidian/conflicts` endpoint
- [ ] UI: Show recent conflicts in settings (optional)

**Files**:
- `app/Services/Obsidian/ConflictDetector.php` (update)
- `app/Console/Commands/ObsidianSyncCommand.php` (update)

**Conflict Log Format**:
```
[2025-01-06 12:34:56] CONFLICT
File: Daily Notes/2025-01-06.md
Fragment ID: 12345
File Modified: 2025-01-06 12:30:00
Fragment Modified: 2025-01-06 12:32:00
Resolution: fragment_wins (last-write-wins strategy)
File Checksum (before): abc123...
File Checksum (after): def456...
```

---

## Phase 3: Nested Folders (P3) - 3 tasks

### T-OBS-26: Create FolderHierarchyService
**Priority**: P3 (Medium)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Parse nested folder structures and create hierarchical tags.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/FolderHierarchyService.php`
- [ ] Parses folder path: `Projects/Active/Q1/Planning.md`
- [ ] Creates hierarchical tags: `projects`, `projects/active`, `projects/active/q1`
- [ ] Normalizes folder names (lowercase, slugify)
- [ ] Stores full hierarchy in metadata
- [ ] Handles root-level files (no folders)
- [ ] Configurable: flat mode (current) vs hierarchy mode

**Files**:
- `app/Services/Obsidian/FolderHierarchyService.php`

**Hierarchy Output**:
```php
// Input: Projects/Active/Q1/Planning.md
// Flat mode: tags = ['q1', 'obsidian']
// Hierarchy mode:
[
    'tags' => ['projects', 'projects/active', 'projects/active/q1', 'obsidian'],
    'metadata' => [
        'obsidian_path' => 'Projects/Active/Q1/Planning.md',
        'obsidian_folder_path' => 'Projects/Active/Q1',
        'obsidian_folder_hierarchy' => ['Projects', 'Active', 'Q1'],
    ],
]
```

---

### T-OBS-27: Enhance ObsidianImportService for nested tags
**Priority**: P3 (Medium)  
**Effort**: 2-3 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Integrate FolderHierarchyService into import pipeline.

**Acceptance Criteria**:
- [ ] Inject `FolderHierarchyService` into `ObsidianImportService`
- [ ] Check settings for hierarchy mode (on/off)
- [ ] Use hierarchy service when enabled
- [ ] Preserve existing flat tag behavior when disabled
- [ ] Update fragment tags with hierarchical structure
- [ ] Backwards compatible with existing imports

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php` (update)

---

### T-OBS-28: Add folder hierarchy toggle to settings
**Priority**: P3 (Low)  
**Effort**: 2 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add UI toggle for folder hierarchy mode.

**Acceptance Criteria**:
- [ ] Backend: Add `preserve_folder_hierarchy` to settings validation
- [ ] UI: Add toggle "Preserve folder hierarchy" with explanation
- [ ] Default: off (flat mode for backwards compatibility)
- [ ] Help text explains difference between modes
- [ ] Settings schema updated

**Files**:
- `app/Http/Controllers/SettingsController.php` (update)
- `resources/js/components/SettingsPage.tsx` (update)

**Settings Schema**:
```json
{
  "integrations": {
    "obsidian": {
      "preserve_folder_hierarchy": false
    }
  }
}
```

---

## Phase 4: Multiple Vaults (P3) - 3 tasks

### T-OBS-29: Create VaultRegistry service
**Priority**: P3 (Medium)  
**Effort**: 4-5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Manage multiple Obsidian vault configurations.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/VaultRegistry.php`
- [ ] Stores vaults in `profile_settings.integrations.obsidian.vaults` (array)
- [ ] CRUD operations: add, remove, update, list vaults
- [ ] Validates unique vault names
- [ ] Validates vault paths don't overlap
- [ ] Returns vault by name or path
- [ ] Migrates existing single vault to array format

**Files**:
- `app/Services/Obsidian/VaultRegistry.php`

**Settings Migration**:
```php
// Before (single vault):
'obsidian' => [
    'vault_path' => '/path/to/vault',
    'sync_enabled' => true,
]

// After (multiple vaults):
'obsidian' => [
    'vaults' => [
        [
            'name' => 'Personal',
            'vault_path' => '/path/to/vault',
            'sync_enabled' => true,
            'sync_direction' => 'import',
            'enrich_enabled' => false,
        ],
    ],
]
```

---

### T-OBS-30: Enhance settings for multi-vault management
**Priority**: P3 (Medium)  
**Effort**: 5-6 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add multi-vault UI with CRUD operations.

**Acceptance Criteria**:
- [ ] Backend: Update settings controller for vault array
- [ ] Backend: Add endpoints: `/obsidian/vaults`, `/obsidian/vaults/{name}`
- [ ] UI: Show list of configured vaults
- [ ] UI: Add vault button with modal form
- [ ] UI: Edit/delete vault actions
- [ ] UI: Per-vault settings (path, sync, enrichment)
- [ ] Migration: auto-convert single vault to array on first load

**Files**:
- `app/Http/Controllers/SettingsController.php` (update)
- `resources/js/components/SettingsPage.tsx` (update)

**UI Design**:
```
Obsidian Vaults
─────────────────────────────────────────
[+ Add Vault]

Personal
  Path: /Users/you/Personal
  Daily Sync: On | AI Enrichment: Off
  [Edit] [Delete]

Work
  Path: /Users/you/Work  
  Daily Sync: On | AI Enrichment: On
  [Edit] [Delete]
```

---

### T-OBS-31: Enhance ObsidianSyncCommand for vault selection
**Priority**: P3 (Medium)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add vault selection to sync command.

**Acceptance Criteria**:
- [ ] Add `--vault` option to specify vault by name
- [ ] Default: sync all enabled vaults
- [ ] Validate vault name exists
- [ ] Show vault name in sync output
- [ ] Per-vault sync statistics
- [ ] Handle vault-specific settings (direction, enrichment)

**Files**:
- `app/Console/Commands/ObsidianSyncCommand.php` (update)

**Command Usage**:
```bash
php artisan obsidian:sync                    # Sync all enabled vaults
php artisan obsidian:sync --vault=Personal   # Sync specific vault
php artisan obsidian:sync --vault=Work --enrich  # Vault-specific override
```

---

## Phase 5: Media & Attachments (P4) - 3 tasks

### T-OBS-32: Create AttachmentImporter service
**Priority**: P4 (Low)  
**Effort**: 5-6 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Import media files and attachments referenced in markdown.

**Acceptance Criteria**:
- [ ] Class created: `app/Services/Obsidian/AttachmentImporter.php`
- [ ] Detects markdown image syntax: `![alt](path/to/image.png)`
- [ ] Detects Obsidian attachment syntax: `![[attachment.pdf]]`
- [ ] Copies files to storage: `storage/app/obsidian/attachments/{fragment_id}/`
- [ ] Stores attachment metadata in fragment
- [ ] Updates markdown to reference storage paths
- [ ] Validates file types (images, PDFs, etc.)
- [ ] Enforces file size limits

**Files**:
- `app/Services/Obsidian/AttachmentImporter.php`

**Technical Notes**:
```php
// Attachment metadata
'metadata' => [
    'obsidian_attachments' => [
        [
            'original_path' => 'attachments/diagram.png',
            'vault_absolute_path' => '/vault/attachments/diagram.png',
            'storage_path' => 'obsidian/attachments/123/diagram.png',
            'file_size' => 45678,
            'mime_type' => 'image/png',
            'imported_at' => '2025-01-06T12:00:00Z',
        ],
    ],
]
```

---

### T-OBS-33: Enhance ObsidianImportService for attachment handling
**Priority**: P4 (Low)  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Integrate attachment importer into sync pipeline.

**Acceptance Criteria**:
- [ ] Inject `AttachmentImporter` into `ObsidianImportService`
- [ ] Check settings for attachment import (on/off)
- [ ] Import attachments after fragment created
- [ ] Update fragment with attachment metadata
- [ ] Handle missing attachment files gracefully
- [ ] Add attachment stats to sync summary

**Files**:
- `app/Services/Obsidian/ObsidianImportService.php` (update)

---

### T-OBS-34: Add attachment settings (UI + backend)
**Priority**: P4 (Low)  
**Effort**: 2-3 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Add settings for attachment import configuration.

**Acceptance Criteria**:
- [ ] Backend: Add attachment settings to validation
- [ ] UI: Toggle "Import attachments"
- [ ] UI: File size limit input (MB)
- [ ] UI: File type filter (checkboxes: Images, PDFs, Videos, etc.)
- [ ] Settings schema updated

**Files**:
- `app/Http/Controllers/SettingsController.php` (update)
- `resources/js/components/SettingsPage.tsx` (update)

**Settings Schema**:
```json
{
  "integrations": {
    "obsidian": {
      "import_attachments": false,
      "attachment_max_size_mb": 10,
      "attachment_types": ["image", "pdf"]
    }
  }
}
```

---

## Phase 6: Testing & Documentation - 4 tasks

### T-OBS-35: Unit tests for new services
**Priority**: High  
**Effort**: 6-8 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create comprehensive unit tests for all new services.

**Acceptance Criteria**:
- [ ] WikilinkParser tests: basic, anchor, alias, edge cases
- [ ] LinkResolver tests: matching, orphans, ambiguous targets
- [ ] FolderHierarchyService tests: nested paths, normalization
- [ ] VaultRegistry tests: CRUD, validation, migration
- [ ] AttachmentImporter tests: file detection, copying, validation
- [ ] All tests pass
- [ ] 90%+ code coverage for new services

**Files**:
- `tests/Unit/Services/WikilinkParserTest.php`
- `tests/Unit/Services/LinkResolverTest.php`
- `tests/Unit/Services/FolderHierarchyServiceTest.php`
- `tests/Unit/Services/VaultRegistryTest.php`
- `tests/Unit/Services/AttachmentImporterTest.php`

---

### T-OBS-36: Feature tests for bidirectional sync
**Priority**: High  
**Effort**: 5-6 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create feature tests for export and conflict resolution.

**Acceptance Criteria**:
- [ ] Test file: `tests/Feature/Console/ObsidianBidirectionalSyncTest.php`
- [ ] Test cases:
  - Export mode writes fragment changes to files
  - Bidirectional mode imports and exports
  - Conflict detection works correctly
  - Last-write-wins resolution applied
  - Dry-run export mode doesn't write files
  - File backups created before overwrite
- [ ] Uses temp directory with fixture files
- [ ] All tests pass

**Files**:
- `tests/Feature/Console/ObsidianBidirectionalSyncTest.php`

---

### T-OBS-37: Integration tests for link resolution
**Priority**: Medium  
**Effort**: 4-5 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Create end-to-end tests for link resolution workflow.

**Acceptance Criteria**:
- [ ] Test file: `tests/Feature/ObsidianLinkResolutionTest.php`
- [ ] Test cases:
  - Wikilinks parsed during import
  - Fragment links created for resolved targets
  - Orphan links tracked correctly
  - Anchor and alias preserved in metadata
  - Link updates on re-import
  - Case-insensitive matching works
- [ ] Uses fixture vault with interlinked notes
- [ ] All tests pass

**Files**:
- `tests/Feature/ObsidianLinkResolutionTest.php`
- `tests/Fixtures/obsidian-vault/` (add linked notes)

---

### T-OBS-38: Update documentation
**Priority**: Medium  
**Effort**: 3-4 hours  
**Status**: Pending  
**Assignee**: Unassigned  

**Description**:
Update documentation with all new features.

**Acceptance Criteria**:
- [ ] Update `docs/ingestion/obsidian-import.md`
- [ ] Add sections:
  - Internal Links (wikilink syntax, resolution, orphans)
  - Bidirectional Sync (setup, conflict resolution, safety)
  - Nested Folders (hierarchy mode)
  - Multiple Vaults (management, per-vault settings)
  - Attachments (supported types, file size limits)
- [ ] Include examples and screenshots
- [ ] Add troubleshooting guide
- [ ] Document migration from Sprint 52 single vault

**Files**:
- `docs/ingestion/obsidian-import.md` (update)

**New Sections**:
```
## Internal Links
How wikilinks are resolved to fragment relationships...

## Bidirectional Sync
Setup two-way sync and handle conflicts...

## Advanced Features
- Nested folder hierarchies
- Multiple vault management
- Attachment imports
```

---

## Summary

| Phase | Tasks | Effort | Priority |
|-------|-------|--------|----------|
| Phase 0: Deterministic Pipeline (P0) 🔥 | 2 | 6-8h | Critical (START HERE) |
| Phase 1: Internal Links (P1) | 5 | 16-21h | High |
| Phase 2: Bidirectional Sync (P2) | 5 | 17-22h | High |
| Phase 3: Nested Folders (P3) | 3 | 7-11h | Medium |
| Phase 4: Multiple Vaults (P3) | 3 | 12-15h | Medium |
| Phase 5: Media/Attachments (P4) | 3 | 10-13h | Low |
| Phase 6: Testing/Docs | 4 | 18-23h | High |
| **Total** | **25** | **86-113h (11-14 days)** | - |

---

## Execution Recommendations

### Option A: Full Sprint (11-14 days)
Execute all 25 tasks in sequence. Best for teams with dedicated resources.

### Option B: Phased Sub-Sprints (Recommended) 🔥
Execute as 6 separate sprints based on priority:

1. **Sprint 67.0 (P0)**: Deterministic Pipeline - 0.5-1 day 🔥 **START HERE**
2. **Sprint 67a (P1)**: Internal Links - 2-3 days
3. **Sprint 67b (P2)**: Bidirectional Sync - 2-3 days
4. **Sprint 67c (P3)**: Nested + Multi-vault - 2-3 days
5. **Sprint 67d (P4)**: Attachments - 1-2 days
6. **Sprint 67e**: Testing + Docs - 1-2 days

### Option C: Minimum Viable (P0 + P1 + P2 only) ⭐ Recommended
Execute Phases 0-2 (12 tasks, 6-7 days) for immediate value:
- **P0**: Intelligent type/tag inference (FREE, FAST)
- **P1**: Wikilink resolution for knowledge graph
- **P2**: Two-way sync for round-trip editing
Defer P3-P4 to future sprints based on user demand.

---

## Dependencies

- SPRINT-52 (Obsidian Vault Import Integration) ✅ COMPLETE
- `fragment_links` table (existing)
- Storage configuration for attachments
- Backup strategy recommendation for users

---

## Next Steps

1. Review task breakdown and priorities
2. Choose execution strategy (full sprint vs sub-sprints)
3. Assign tasks to agents
4. Create sub-sprint plans if using phased approach
5. Begin implementation with T-OBS-16

**Ready to start Sprint 67.0 (Deterministic Pipeline)?** 🚀 🔥 **START HERE!**

This foundation task will dramatically improve type accuracy and tagging for ALL Obsidian imports, making subsequent phases even more powerful.
