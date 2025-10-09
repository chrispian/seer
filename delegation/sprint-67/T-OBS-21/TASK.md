# T-OBS-21: Create ObsidianWriteService

**Task Code**: `T-OBS-21`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P2-HIGH  
**Status**: `pending`  
**Estimated**: 5-6 hours  
**Dependencies**: None

## Objective

Create service to write fragment changes back to Obsidian markdown files.

## Acceptance Criteria

- [ ] Class created: `app/Services/Obsidian/ObsidianWriteService.php`
- [ ] Writes fragment message to `.md` file
- [ ] Preserves YAML front matter structure
- [ ] Updates front matter from fragment tags/metadata
- [ ] Handles file creation for new fragments (no obsidian_path)
- [ ] Updates file modification timestamp
- [ ] Validates path before writing (security)
- [ ] Logs all file writes with before/after snapshots

## Files

- `app/Services/Obsidian/ObsidianWriteService.php` (new)

## Technical Notes

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

## Safety

- Validate path is within vault directory (prevent path traversal)
- Backup file content before overwrite
- Use file locks during write

## Testing Requirements

- [ ] Test file write with front matter preservation
- [ ] Test new file creation
- [ ] Test path validation and security
- [ ] Test file backup creation
- [ ] Test atomic write operations
