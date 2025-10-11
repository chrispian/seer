# Task: T-FE-UI-20-REFACTOR - Refactor Config Field Naming

**Sprint**: SPRINT-FE-UI-1  
**Type**: Refactor  
**Priority**: High  
**Estimate**: 1-2 hours  
**Status**: In Progress

## Context

Current config fields are semantically unclear:
- `ui_modal_container` contains base primitives (DataManagementModal) instead of actual modals
- No distinction between top-level modals vs base renderers vs card components

This causes resolution errors where the wrong component is selected.

## Solution: Semantic Field Naming

### New Structure
- `ui_modal_container` → Actual modal components ONLY (SprintListModal, TaskListModal, etc.)
- `ui_base_renderer` → Base primitives (DataManagementModal, KanbanBoard, etc.) [NEW]
- `ui_card_component` → Card components (SprintCard, TaskCard, etc.)

## Implementation Steps

### Step 1: Create Migration
Add `ui_base_renderer` column to `commands` table.

### Step 2: Update Command Seeds
Fix all 12+ commands to have correct `ui_modal_container` values.

### Step 3: Update Frontend Resolution
File: `resources/js/islands/chat/CommandResultModal.tsx`
- Update `getComponentName()` to use corrected priority
- Ensure modal_container values must exist in COMPONENT_MAP

### Step 4: Update Backend
File: `app/Commands/BaseCommand.php`
- Add `ui_base_renderer` to `getUIConfig()` method

## Acceptance Criteria
- [ ] Migration adds ui_base_renderer field
- [ ] All commands have correct modal_container (SprintListModal not DataManagementModal)
- [ ] Frontend resolution logic updated
- [ ] Build succeeds
- [ ] `/sprints` command renders SprintListModal correctly
- [ ] Console logs show correct component resolution
