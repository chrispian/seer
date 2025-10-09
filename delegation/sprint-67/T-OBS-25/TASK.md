# T-OBS-25: Add conflict resolution logging and reporting

**Task Code**: `T-OBS-25`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P2-MEDIUM  
**Status**: `pending`  
**Estimated**: 2-3 hours  
**Dependencies**: T-OBS-22, T-OBS-23

## Objective

Create comprehensive logging for conflict detection and resolution.

## Acceptance Criteria

- [ ] Log all conflicts to `storage/logs/obsidian-conflicts.log`
- [ ] Log format: timestamp, file path, resolution decision, checksums
- [ ] Add conflict summary to sync command output
- [ ] Store conflict history in settings metadata
- [ ] Create `/settings/integrations/obsidian/conflicts` endpoint
- [ ] UI: Show recent conflicts in settings (optional)

## Files

- `app/Services/Obsidian/ConflictDetector.php` (update)
- `app/Console/Commands/ObsidianSyncCommand.php` (update)

## Conflict Log Format

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

## Testing Requirements

- [ ] Test conflict logging to dedicated log file
- [ ] Test log format and content
- [ ] Test conflict summary in command output
- [ ] Test conflict history storage
- [ ] Test conflicts endpoint (if implemented)
