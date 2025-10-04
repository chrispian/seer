# Fix /todos Command Routing Issue

## Problem
The `/todos` slash command is not properly routing to the TodoManagementModal. Instead, it bypasses the local modal handling and executes the API command, showing results in the CommandResultModal.

## Current Behavior
- `/todos` → Shows "25 todos found" in CommandResultModal (wrong)
- `/todos-ui` → Shows TodoManagementModal (correct)
- `/todo-list` → Shows TodoManagementModal (correct)

## Expected Behavior
All todo UI commands should route to the TodoManagementModal:
- `/todos` → TodoManagementModal
- `/todos-ui` → TodoManagementModal 
- `/todo-list` → TodoManagementModal

## Technical Details
The issue is in `ChatIsland.tsx` around line 268 where the command matching logic may not be properly catching the `/todos` command, causing it to fall through to the API execution instead of setting `isTodoModalOpen = true`.

## Investigation Needed
1. Check command parsing logic in `handleCommand` function
2. Verify if there's a conflict with other command registrations
3. Test command matching conditions
4. Check for potential JavaScript errors preventing modal opening

## Priority
Medium - Workaround exists (`/todos-ui`, `/todo-list` work correctly)

## Sprint
Backlog for upcoming command system refactoring sprint