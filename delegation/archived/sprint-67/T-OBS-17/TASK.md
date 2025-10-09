# T-OBS-17: Create LinkResolver service

**Task Code**: `T-OBS-17`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P1-HIGH  
**Status**: `pending`  
**Estimated**: 4-5 hours  
**Dependencies**: T-OBS-16

## Objective

Create service to resolve wikilink targets to fragment IDs and create fragment_links records.

## Acceptance Criteria

- [ ] Class created: `app/Services/Obsidian/LinkResolver.php`
- [ ] Builds filename→fragment_id lookup table from vault fragments
- [ ] Matches link targets to fragments (case-insensitive)
- [ ] Handles ambiguous targets (multiple matches) → logs warning, uses first match
- [ ] Tracks orphan links (no matching fragment) → returns orphan list
- [ ] Creates `fragment_links` records with obsidian metadata
- [ ] Returns resolution stats: total_links, resolved, orphaned, ambiguous

## Files

- `app/Services/Obsidian/LinkResolver.php` (new)

## Technical Notes

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

## Testing Requirements

- [ ] Test link resolution to existing fragments
- [ ] Test orphan link detection
- [ ] Test ambiguous target handling
- [ ] Test case-insensitive matching
- [ ] Test fragment_links creation with metadata
- [ ] Test resolution statistics
