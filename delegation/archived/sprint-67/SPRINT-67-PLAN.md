# SPRINT-67: Obsidian Advanced Features - Internal Links & Bi-Directional Sync

**Status**: 📋 PLANNED  
**Sprint Goal**: Enhance Obsidian integration with internal link resolution and bidirectional sync capabilities  
**Estimated Effort**: 5-7 days  
**Priority**: MEDIUM-HIGH  
**Dependencies**: SPRINT-52 (Obsidian Vault Import Integration) ✅ COMPLETE  
**Completion Date**: TBD

---

## Overview

Extend the Obsidian integration beyond basic import to support advanced features that make Fragments Engine a true bidirectional knowledge management system. This sprint adds two major capabilities: internal link resolution (creating fragment relationships from `[[wikilinks]]`) and bidirectional sync (writing changes back to Obsidian vault).

### Scope Definition

**In Scope:**

**Phase 1: Internal Links (P1)**
- Parse `[[wikilinks]]` and create `fragment_links` records
- Support `[[note#heading]]` anchor links
- Handle aliases `[[note|display text]]`
- Link by filename matching (case-insensitive)
- Orphan link tracking (links to non-existent notes)
- Link visualization in fragment detail view

**Phase 2: Bidirectional Sync (P2)**
- Detect changes in Fragments Engine vs filesystem
- Write fragment updates back to `.md` files
- Preserve front matter structure
- Handle concurrent edits (last-write-wins for MVP)
- Conflict detection and logging
- Sync direction toggle (one-way vs two-way)

**Phase 3: Folder Hierarchies (P3)**
- Nested folder support with parent-child tags
- Preserve folder structure in fragment metadata
- Query fragments by folder path
- Folder-based project organization option

**Phase 4: Multiple Vaults (P3)**
- Support multiple Obsidian vault paths
- Vault-specific settings and sync schedules
- Vault selection in import UI
- Cross-vault link handling

**Phase 5: Media & Attachments (P4)**
- Import images referenced in markdown
- Store attachments with fragments
- Handle PDF, audio, video embeds
- Attachment sync to filesystem

**Out of Scope (Future Versions):**
- Real-time file watching (requires filesystem events)
- AI-powered merge conflict resolution
- Obsidian plugin development
- Dataview query support
- Template variable expansion
- Canvas file support

---

## Architecture Design

### Phase 0: Deterministic Pipeline Architecture

**Problem**: Current implementation has basic type/tag assignment. Need intelligent, deterministic pipeline for:
- Path-based type inference (e.g., `Contacts/` → `contact` type)
- Front matter extraction for types and custom metadata
- Folder-based tag generation
- Content-based classification without AI (faster, free)

**Components:**

1. **ObsidianFragmentPipeline** (`app/Services/Obsidian/ObsidianFragmentPipeline.php`)
   - Deterministic pipeline for fragment enrichment
   - Path-based type inference rules
   - Front matter metadata extraction
   - Folder-to-tag mapping
   - Content pattern matching for types

2. **Pipeline Steps (Deterministic)**:
   - **Step 1**: Extract front matter metadata (title, tags, type, custom fields)
   - **Step 2**: Infer type from folder path (Contacts → contact, Meetings → meeting, etc.)
   - **Step 3**: Infer type from front matter `type:` field
   - **Step 4**: Fallback pattern matching (meeting notes, task lists, etc.)
   - **Step 5**: Generate tags from folders, front matter, and content patterns
   - **Step 6**: Extract custom metadata from front matter

**Type Inference Rules** (priority order):

```php
// 1. Front matter explicit type (highest priority)
if (isset($frontMatter['type'])) {
    return $frontMatter['type']; // e.g., type: meeting
}

// 2. Path-based rules
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

foreach ($pathRules as $pathPattern => $type) {
    if (str_contains($relativePath, $pathPattern)) {
        return $type;
    }
}

// 3. Content pattern matching
$contentPatterns = [
    '/^#+ Meeting:/' => 'meeting',
    '/^- \[[ x]\]/' => 'task', // Checkbox list
    '/^## Action Items/' => 'meeting',
    '/^Project:/' => 'project',
];

foreach ($contentPatterns as $pattern => $type) {
    if (preg_match($pattern, $content)) {
        return $type;
    }
}

// 4. Default fallback
return 'note';
```

**Tag Generation Rules**:

```php
$tags = [];

// 1. Front matter tags (array or comma-separated)
if (isset($frontMatter['tags'])) {
    $tags = array_merge($tags, $this->parseFrontMatterTags($frontMatter['tags']));
}

// 2. Folder-based tags (all parent folders)
$folderTags = $this->extractFolderTags($relativePath);
$tags = array_merge($tags, $folderTags);

// 3. Content-based tags (hashtags in content)
$contentTags = $this->extractHashtags($content);
$tags = array_merge($tags, $contentTags);

// 4. Always add source tag
$tags[] = 'obsidian';

// 5. Normalize and deduplicate
return array_unique(array_map('Str::slug', $tags));
```

**Custom Metadata Extraction**:

```php
// Front matter fields to extract
$customFields = [
    'author',
    'date',
    'project',
    'priority',
    'status',
    'category',
    'url',
    'source_url',
    // ... user can add more via config
];

$metadata = [];
foreach ($customFields as $field) {
    if (isset($frontMatter[$field])) {
        $metadata[$field] = $frontMatter[$field];
    }
}
```

---

### Phase 1: Internal Links Architecture

**Components:**

1. **WikilinkParser** (`app/Services/Obsidian/WikilinkParser.php`)
   - Extract `[[target]]` links from markdown content
   - Parse `[[target#heading]]` anchor syntax
   - Parse `[[target|alias]]` display text syntax
   - Return structured link data (target, heading, alias, position)

2. **LinkResolver** (`app/Services/Obsidian/LinkResolver.php`)
   - Match link targets to existing fragments
   - Handle case-insensitive filename matching
   - Track orphan links (targets not found)
   - Create `fragment_links` records with metadata

3. **ObsidianMarkdownParser Enhancement**
   - Extract wikilinks during parsing (currently strips them)
   - Return links array in `ParsedObsidianNote` DTO
   - Preserve original link syntax in body

4. **ObsidianImportService Enhancement**
   - Second pass: resolve links after all fragments imported
   - Create/update `fragment_links` table entries
   - Track link metadata: anchor, alias, orphan status

**Data Model:**

```php
// Enhance fragment_links table
[
    'source_fragment_id' => $fragmentId,
    'target_fragment_id' => $linkedFragmentId,
    'metadata' => [
        'obsidian_link' => true,
        'link_text' => '[[Project Plan#Goals]]',
        'target_filename' => 'Project Plan.md',
        'anchor' => 'Goals',
        'alias' => null,
        'is_orphan' => false, // true if target not found
        'position' => 42, // character offset in source
    ],
]
```

**Link Resolution Algorithm:**

1. Parse all wikilinks from markdown during import
2. Store raw link data in `metadata->obsidian_links`
3. After all files imported, run link resolution pass:
   - Find fragments by filename match (case-insensitive)
   - Create `fragment_links` records for matches
   - Log orphan links (no matching fragment)
4. On subsequent syncs, update links if targets change

---

### Phase 2: Bidirectional Sync Architecture

**Components:**

1. **ObsidianWriteService** (`app/Services/Obsidian/ObsidianWriteService.php`)
   - Write fragment changes back to `.md` files
   - Preserve front matter structure
   - Update file modification timestamps
   - Handle file creation for new fragments

2. **ConflictDetector** (`app/Services/Obsidian/ConflictDetector.php`)
   - Compare fragment `updated_at` vs file `mtime`
   - Detect concurrent edits (both changed since last sync)
   - Log conflicts for manual resolution
   - Strategy: last-write-wins (configurable)

3. **ObsidianSyncCommand Enhancement**
   - Add `--direction` flag: `import`, `export`, `bidirectional` (default: `import`)
   - Export mode: write fragment changes to files
   - Bidirectional mode: import new/updated files, export fragment changes

4. **Settings Enhancement**
   - Sync direction setting: one-way (import) vs two-way (sync)
   - Conflict resolution strategy: last-write-wins vs manual
   - Enable/disable write-back per vault

**Sync Flow (Bidirectional Mode):**

```
1. Read filesystem state (file mtimes)
2. Read fragment state (updated_at timestamps)
3. Identify changes:
   - Files newer than fragments → Import
   - Fragments newer than files → Export
   - Both newer → Conflict (log + apply strategy)
4. Import changed files (existing flow)
5. Export changed fragments (new)
6. Update sync timestamps
```

**Conflict Resolution (MVP: Last-Write-Wins):**

```php
if ($fileModified && $fragmentModified) {
    if ($fileMtime > $fragment->updated_at) {
        // File wins: import
        $this->importFile($file);
        Log::info("Conflict resolved: file wins", [...]);
    } else {
        // Fragment wins: export
        $this->exportFragment($fragment);
        Log::info("Conflict resolved: fragment wins", [...]);
    }
}
```

---

### Phase 3: Nested Folders Architecture

**Components:**

1. **FolderHierarchyService** (`app/Services/Obsidian/FolderHierarchyService.php`)
   - Parse nested folder paths (e.g., `Projects/Active/Q1/Planning.md`)
   - Create hierarchical tags: `projects`, `projects/active`, `projects/active/q1`
   - Store full path in metadata: `obsidian_folder_path`

2. **Settings Enhancement**
   - Toggle: "Preserve folder hierarchy" (on/off)
   - When on: create nested tags
   - When off: use current flat folder-as-tag logic

**Folder Tag Structure:**

```php
// File: Projects/Active/Q1/Planning.md
// Flat mode (current): tags = ['q1', 'obsidian']
// Hierarchy mode (new): tags = ['projects', 'projects/active', 'projects/active/q1', 'obsidian']

// Fragment metadata:
'metadata' => [
    'obsidian_path' => 'Projects/Active/Q1/Planning.md',
    'obsidian_folder_path' => 'Projects/Active/Q1',
    'obsidian_folder_hierarchy' => ['Projects', 'Active', 'Q1'],
]
```

---

### Phase 4: Multiple Vaults Architecture

**Components:**

1. **VaultRegistry** (`app/Services/Obsidian/VaultRegistry.php`)
   - Manage multiple vault configurations
   - Store in `profile_settings.integrations.obsidian.vaults` (array)
   - Vault properties: name, path, sync_enabled, last_synced_at

2. **Settings Enhancement**
   - UI: List of vaults with add/remove/edit
   - Per-vault settings: path, sync schedule, enrichment

3. **ObsidianSyncCommand Enhancement**
   - Add `--vault` option to specify vault by name
   - Default: sync all enabled vaults
   - Example: `php artisan obsidian:sync --vault=work`

**Settings Schema:**

```json
{
  "integrations": {
    "obsidian": {
      "vaults": [
        {
          "name": "Personal",
          "vault_path": "/Users/you/Personal",
          "sync_enabled": true,
          "enrich_enabled": false,
          "sync_direction": "import",
          "last_synced_at": "2025-01-06T12:00:00Z"
        },
        {
          "name": "Work",
          "vault_path": "/Users/you/Work",
          "sync_enabled": true,
          "enrich_enabled": true,
          "sync_direction": "bidirectional",
          "last_synced_at": "2025-01-06T12:05:00Z"
        }
      ]
    }
  }
}
```

---

### Phase 5: Media & Attachments Architecture

**Components:**

1. **AttachmentImporter** (`app/Services/Obsidian/AttachmentImporter.php`)
   - Detect media references in markdown: `![](image.png)`, `![[attachment.pdf]]`
   - Copy files to storage (e.g., `storage/app/obsidian/attachments/{fragment_id}/`)
   - Create attachment records linked to fragments
   - Update markdown to use storage paths

2. **Settings Enhancement**
   - Toggle: "Import attachments" (on/off)
   - Max file size limit
   - Allowed file types (images, PDFs, etc.)

**Attachment Metadata:**

```php
'metadata' => [
    'obsidian_attachments' => [
        [
            'original_path' => 'attachments/diagram.png',
            'storage_path' => 'obsidian/attachments/123/diagram.png',
            'file_size' => 45678,
            'mime_type' => 'image/png',
        ],
    ],
]
```

---

## Task Breakdown

### Phase 0: Deterministic Pipeline Enhancement (P0) - 2 tasks
- **T-OBS-15.5**: Create ObsidianFragmentPipeline service
- **T-OBS-15.6**: Enhance ObsidianImportService with pipeline integration

### Phase 1: Internal Links (P1) - 5 tasks
- **T-OBS-16**: Create WikilinkParser service
- **T-OBS-17**: Create LinkResolver service
- **T-OBS-18**: Enhance ObsidianMarkdownParser for link extraction
- **T-OBS-19**: Enhance ObsidianImportService for link resolution
- **T-OBS-20**: Add link visualization to fragment UI

### Phase 2: Bidirectional Sync (P2) - 5 tasks
- **T-OBS-21**: Create ObsidianWriteService
- **T-OBS-22**: Create ConflictDetector service
- **T-OBS-23**: Enhance ObsidianSyncCommand for bidirectional sync
- **T-OBS-24**: Add sync direction settings (UI + backend)
- **T-OBS-25**: Add conflict resolution logging and reporting

### Phase 3: Nested Folders (P3) - 3 tasks
- **T-OBS-26**: Create FolderHierarchyService
- **T-OBS-27**: Enhance ObsidianImportService for nested tags
- **T-OBS-28**: Add folder hierarchy toggle to settings

### Phase 4: Multiple Vaults (P3) - 3 tasks
- **T-OBS-29**: Create VaultRegistry service
- **T-OBS-30**: Enhance settings for multi-vault management
- **T-OBS-31**: Enhance ObsidianSyncCommand for vault selection

### Phase 5: Media & Attachments (P4) - 3 tasks
- **T-OBS-32**: Create AttachmentImporter service
- **T-OBS-33**: Enhance ObsidianImportService for attachment handling
- **T-OBS-34**: Add attachment settings (UI + backend)

### Phase 6: Testing & Documentation - 4 tasks
- **T-OBS-35**: Unit tests for new services (WikilinkParser, LinkResolver, etc.)
- **T-OBS-36**: Feature tests for bidirectional sync
- **T-OBS-37**: Integration tests for full link resolution workflow
- **T-OBS-38**: Update documentation with new features

**Total: 25 tasks across 7 phases**

---

## Phased Rollout Strategy

### Recommended Execution Order

**Sprint 67.0: Deterministic Pipeline (P0)** - 0.5-1 day 🔥 START HERE
- Tasks: T-OBS-15.5, T-OBS-15.6
- Deliverable: Intelligent path/front-matter-based type inference and tagging

**Sprint 67a: Internal Links (P1)** - 2-3 days
- Tasks: T-OBS-16 through T-OBS-20
- Deliverable: Wikilink resolution with fragment_links creation

**Sprint 67b: Bidirectional Sync (P2)** - 2-3 days
- Tasks: T-OBS-21 through T-OBS-25
- Deliverable: Two-way sync with conflict detection

**Sprint 67c: Advanced Features (P3)** - 2-3 days
- Tasks: T-OBS-26 through T-OBS-31
- Deliverable: Nested folders + multiple vaults

**Sprint 67d: Media Support (P4)** - 1-2 days
- Tasks: T-OBS-32 through T-OBS-34
- Deliverable: Attachment import and sync

**Sprint 67e: Quality Assurance** - 1-2 days
- Tasks: T-OBS-35 through T-OBS-38
- Deliverable: Comprehensive test coverage and docs

---

## Success Criteria

**Phase 1 (Internal Links):**
- [ ] Wikilinks parsed and stored during import
- [ ] Fragment links created for resolved targets
- [ ] Orphan links tracked and logged
- [ ] Link visualization in fragment detail view
- [ ] Anchor links (`[[note#heading]]`) supported
- [ ] Alias syntax (`[[note|alias]]`) supported

**Phase 2 (Bidirectional Sync):**
- [ ] Fragment changes written back to `.md` files
- [ ] Concurrent edits detected and resolved
- [ ] Sync direction configurable (one-way vs two-way)
- [ ] Conflict logs show resolution decisions
- [ ] Front matter preserved during export
- [ ] File timestamps updated correctly

**Phase 3 (Nested Folders):**
- [ ] Hierarchical tags created for nested folders
- [ ] Folder path preserved in metadata
- [ ] Toggle works in settings (flat vs hierarchy mode)

**Phase 4 (Multiple Vaults):**
- [ ] Multiple vaults configurable in settings
- [ ] Per-vault sync settings work
- [ ] Vault selection in CLI works (`--vault=work`)
- [ ] Cross-vault links handled gracefully

**Phase 5 (Media & Attachments):**
- [ ] Images imported and stored
- [ ] Markdown updated with storage paths
- [ ] Attachment metadata tracked
- [ ] File size/type limits enforced

**Overall:**
- [ ] All tests pass (90%+ coverage)
- [ ] Documentation complete
- [ ] No breaking changes to Sprint 52 functionality

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Link resolution performance (large vaults) | High | Batch processing, cache filename→fragment map |
| Concurrent edit conflicts | High | Clear logging, last-write-wins strategy |
| Data loss during export | Critical | Backup vault before enabling two-way sync |
| Wikilink ambiguity (duplicate filenames) | Medium | Use full path matching when ambiguous |
| Attachment storage growth | Medium | File size limits, cleanup old attachments |
| Breaking existing imports | High | Feature flags for all new behavior |

---

## Technical Notes

### Link Resolution Performance

For large vaults (1000+ notes), pre-build a filename→fragment_id map:

```php
// Build lookup table once per sync
$filenameMap = Fragment::where('source_key', 'obsidian')
    ->get()
    ->keyBy(fn($f) => strtolower($this->extractFilename($f->metadata['obsidian_path'])));

// Fast lookup during link resolution
foreach ($links as $link) {
    $targetId = $filenameMap[strtolower($link['target'])] ?? null;
    if ($targetId) {
        FragmentLink::create([...]);
    } else {
        Log::warning("Orphan link: {$link['target']}");
    }
}
```

### Conflict Resolution Strategies

**MVP: Last-Write-Wins**
- Simple: newest timestamp wins
- Risk: potential data loss

**Future: Manual Resolution**
- Show conflict UI in fragment detail
- Side-by-side diff view
- User chooses file vs fragment version

**Future: AI-Powered Merge**
- Use AI to merge both versions
- Preserve all content, resolve formatting conflicts

### Bidirectional Sync Safety

**Safeguards:**
- Require explicit opt-in for two-way sync
- Warn users to backup vault before enabling
- Log all file writes with before/after content
- Provide "revert last sync" command
- Dry-run mode shows what would be written

---

## Dependencies

- SPRINT-52 (Obsidian Vault Import Integration) ✅ COMPLETE
- `fragment_links` table (existing)
- Storage configuration for attachments
- Backup strategy for vaults

---

## Estimated Timeline

- **Phase 0 (Deterministic Pipeline)**: 0.5-1 day 🔥 START HERE
- **Phase 1 (Internal Links)**: 2-3 days
- **Phase 2 (Bidirectional Sync)**: 2-3 days
- **Phase 3 (Nested Folders)**: 1-2 days
- **Phase 4 (Multiple Vaults)**: 1-2 days
- **Phase 5 (Media/Attachments)**: 1-2 days
- **Phase 6 (Testing/Docs)**: 1-2 days
- **Buffer**: 1 day

**Total: 10-16 days (can be split into sub-sprints)**

---

## Review & Sign-off

- [ ] Architecture reviewed
- [ ] Task breakdown approved
- [ ] Sprint ready to start

**Created**: 2025-10-06  
**Author**: AI Assistant  
**Reviewer**: @chrispian
