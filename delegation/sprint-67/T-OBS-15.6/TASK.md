# T-OBS-15.6: Enhance ObsidianImportService with pipeline

**Task Code**: `T-OBS-15.6`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P0-CRITICAL  
**Status**: `pending`  
**Estimated**: 2-3 hours  
**Dependencies**: T-OBS-15.5

## Objective

Integrate ObsidianFragmentPipeline into import flow, replacing simple type assignment with intelligent pipeline.

## Acceptance Criteria

- [ ] Inject `ObsidianFragmentPipeline` into `ObsidianImportService`
- [ ] Run pipeline after markdown parsing
- [ ] Use pipeline-inferred type instead of hardcoded 'note'
- [ ] Merge pipeline tags with existing tag logic
- [ ] Store custom metadata in fragment metadata field
- [ ] Pipeline runs before AI enrichment (deterministic first)
- [ ] AI enrichment can still override type if enabled
- [ ] Backwards compatible with existing imports
- [ ] Add pipeline stats to import summary

## Files

- `app/Services/Obsidian/ObsidianImportService.php` (update)

## Implementation Changes

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

## Stats Enhancement

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

## Testing Requirements

- [ ] Verify pipeline runs for all imports
- [ ] Verify type correctly inferred from paths
- [ ] Verify tags merged from pipeline + folders
- [ ] Verify custom metadata stored
- [ ] Verify AI enrichment still works when enabled
