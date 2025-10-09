# T-OBS-20: Add link visualization to fragment UI

**Task Code**: `T-OBS-20`  
**Sprint**: Sprint 67 - Obsidian Advanced Features  
**Priority**: P1-MEDIUM  
**Status**: `pending`  
**Estimated**: 3-4 hours  
**Dependencies**: T-OBS-19

## Objective

Display linked fragments in fragment detail view.

## Acceptance Criteria

- [ ] Show "Linked Notes" section in fragment detail modal
- [ ] List outbound links (links from this fragment)
- [ ] List inbound links (links to this fragment)
- [ ] Click link to navigate to target fragment
- [ ] Show orphan links with warning icon
- [ ] Display anchor/alias if present
- [ ] Handle no links gracefully (hide section)

## Files

- `resources/js/components/FragmentDetail.tsx` (or similar)

## Design Notes

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

## Testing Requirements

- [ ] Test outbound links display
- [ ] Test inbound links display
- [ ] Test link navigation
- [ ] Test orphan link warning
- [ ] Test no links state
