# Complete YAML Command Migration

## Summary
Sprint 46 attempted to migrate all hardcoded PHP commands to the unified YAML DSL system, but the migration remains incomplete. Currently, we have a dual-command system where both hardcoded PHP commands and YAML DSL commands coexist, creating maintenance overhead and potential conflicts.

## Current State
After recent orchestration UI work, we had to **restore hardcoded PHP commands** that were commented out in `CommandRegistry.php` because users were missing essential commands like `/help`, `/clear`, `/search`, etc.

### Commands Currently Hardcoded (need YAML migration):
- `/session` - Session management
- `/help` - Help system  
- `/clear` - Clear chat history
- `/search` & `/s` - Search functionality
- `/frag` - Fragment creation
- `/bookmark` - Bookmark conversations
- `/join` & `/j` - Join channels
- `/channels` - List channels
- `/name` - Set channel names
- `/routing` - Routing commands
- `/inbox` - Inbox functionality
- `/recall` - Fragment recall
- `/todo` & `/t` - Todo management
- `/vault` & `/v` - Vault operations
- `/project` & `/p` - Project management
- `/context` & `/ctx` - Context management
- `/compose` & `/c` - Compose operations

### Commands Already in YAML:
Based on `fragments/commands/` directory:
- `/help` - Has YAML but PHP version active
- `/clear` - Has YAML but PHP version active  
- `/search` - Has YAML but PHP version active
- `/frag` - Has YAML but PHP version active
- `/bookmark` - Has YAML but PHP version active
- `/join` - Has YAML but PHP version active
- `/channels` - Has YAML but PHP version active
- `/name` - Has YAML but PHP version active
- `/session` - Has YAML but PHP version active
- And others...

## Problem Statement
1. **Dual System Complexity**: Maintaining both systems increases complexity
2. **Feature Gaps**: Some features may exist in one system but not the other
3. **User Confusion**: Inconsistent behavior between similar commands
4. **Development Overhead**: Changes need to be made in multiple places
5. **Technical Debt**: Temporary solution becoming permanent

## Historical Context
- **Sprint 46** (archived): Attempted full migration, marked as "completed" but dual system persists
- **Recent Issue**: Had to un-comment hardcoded commands because YAML versions weren't accessible to users
- **Root Cause**: YAML command loading/routing may not be fully functional

## Required Investigation
1. **YAML Command Loading**: Why aren't YAML commands accessible when PHP commands are disabled?
2. **Feature Parity**: Compare PHP vs YAML implementations for functional differences
3. **Performance Impact**: Assess any performance differences between systems
4. **Routing Integration**: Ensure YAML commands integrate properly with the routing system

## Success Criteria
- [ ] All hardcoded commands successfully migrated to functional YAML equivalents
- [ ] YAML commands fully accessible and working in the UI
- [ ] All command aliases and shortcuts preserved
- [ ] No regression in functionality or performance
- [ ] `CommandRegistry.php` cleaned up (only orchestration commands remain)
- [ ] Dual system eliminated

## Priority: Medium-High
This affects system maintainability and user experience but isn't blocking core functionality.

## Estimated Effort: 3-5 days
- Investigation: 1 day
- Migration work: 2-3 days  
- Testing and validation: 1 day

## Dependencies
- YAML DSL command system must be fully functional
- Command routing system must support YAML commands
- Frontend slash command processing must work with YAML commands

## Related Work
- **Sprint 46**: Previous migration attempt (see archived documentation)
- **Recent Orchestration UI**: Required restoration of hardcoded commands
- **Command System Architecture**: Current dual-system state