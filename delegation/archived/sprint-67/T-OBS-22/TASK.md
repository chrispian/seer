# T-OBS-22: Create ConflictDetector service

**Task Code**: `T-OBS-22`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P2-HIGH  
**Status**: `pending`  
**Estimated**: 3-4 hours  
**Dependencies**: None

## Objective

Detect conflicts between filesystem and fragment changes.

## Acceptance Criteria

- [ ] Class created: `app/Services/Obsidian/ConflictDetector.php`
- [ ] Compares file `mtime` vs fragment `updated_at`
- [ ] Detects three states: file_newer, fragment_newer, conflict
- [ ] Conflict = both changed since last sync
- [ ] Returns conflict report with resolution recommendation
- [ ] Logs conflicts to dedicated log file
- [ ] Strategy: last-write-wins (configurable)

## Files

- `app/Services/Obsidian/ConflictDetector.php` (new)

## Conflict Detection Logic

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

## Testing Requirements

- [ ] Test file_newer detection
- [ ] Test fragment_newer detection
- [ ] Test conflict detection (both changed)
- [ ] Test in_sync detection
- [ ] Test conflict logging
