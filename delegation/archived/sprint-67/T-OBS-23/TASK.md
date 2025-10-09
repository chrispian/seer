# T-OBS-23: Enhance ObsidianSyncCommand for bidirectional sync

**Task Code**: `T-OBS-23`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P2-HIGH  
**Status**: `pending`  
**Estimated**: 4-5 hours  
**Dependencies**: T-OBS-21, T-OBS-22

## Objective

Add bidirectional sync capabilities to CLI command.

## Acceptance Criteria

- [ ] Add `--direction` option: `import`, `export`, `bidirectional`
- [ ] Default direction from settings (fallback: `import`)
- [ ] Import mode: existing behavior (read files)
- [ ] Export mode: write fragment changes to files
- [ ] Bidirectional mode: import + export with conflict detection
- [ ] Show conflict resolution decisions in output
- [ ] Add `--dry-run` support for export operations
- [ ] Summary includes: files_imported, files_exported, conflicts_detected

## Files

- `app/Console/Commands/ObsidianSyncCommand.php` (update)

## Command Usage

```bash
php artisan obsidian:sync --direction=import       # Read-only (default)
php artisan obsidian:sync --direction=export       # Write-only
php artisan obsidian:sync --direction=bidirectional # Two-way sync
php artisan obsidian:sync --dry-run --direction=export  # Preview exports
```

## Testing Requirements

- [ ] Test import mode
- [ ] Test export mode
- [ ] Test bidirectional mode
- [ ] Test conflict detection and resolution
- [ ] Test dry-run for exports
