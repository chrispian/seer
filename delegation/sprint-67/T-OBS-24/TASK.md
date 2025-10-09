# T-OBS-24: Add sync direction settings (UI + backend)

**Task Code**: `T-OBS-24`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P2-MEDIUM  
**Status**: `pending`  
**Estimated**: 3-4 hours  
**Dependencies**: T-OBS-23

## Objective

Add settings for bidirectional sync configuration.

## Acceptance Criteria

- [ ] Backend: Add `sync_direction` to settings validation
- [ ] Backend: Add `conflict_resolution` strategy setting
- [ ] UI: Add "Sync Direction" dropdown: One-way (Import) / Two-way (Sync)
- [ ] UI: Add conflict resolution strategy selector
- [ ] UI: Warning message for two-way sync (backup recommendation)
- [ ] Settings schema updated with new fields

## Files

- `app/Http/Controllers/SettingsController.php` (update)
- `resources/js/components/SettingsPage.tsx` (update)

## Settings Schema

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

## Testing Requirements

- [ ] Test settings validation for sync_direction
- [ ] Test settings validation for conflict_resolution
- [ ] Test UI sync direction dropdown
- [ ] Test UI conflict resolution selector
- [ ] Test warning message display
