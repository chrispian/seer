# T-OBS-19: Enhance ObsidianImportService for link resolution

**Task Code**: `T-OBS-19`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P1-HIGH  
**Status**: `pending`  
**Estimated**: 4-5 hours  
**Dependencies**: T-OBS-17, T-OBS-18

## Objective

Add two-pass import: first pass imports fragments, second pass resolves links.

## Acceptance Criteria

- [ ] Inject `LinkResolver` into `ObsidianImportService`
- [ ] Store parsed links in `metadata->obsidian_links` during first pass
- [ ] After all files imported, run link resolution pass
- [ ] Create/update `fragment_links` for resolved links
- [ ] Log orphan links with file path and link target
- [ ] Add link resolution stats to import summary
- [ ] Handle incremental syncs: update links only for changed files

## Files

- `app/Services/Obsidian/ObsidianImportService.php` (update)

## Import Flow

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

## Testing Requirements

- [ ] Test two-pass import workflow
- [ ] Test link resolution and fragment_links creation
- [ ] Test orphan link logging
- [ ] Test incremental sync link updates
- [ ] Test link resolution statistics
