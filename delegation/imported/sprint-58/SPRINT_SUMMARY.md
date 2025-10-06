# Sprint 58: DSL Slash Command UX Enhancement

## Overview
Enhance the DSL-driven slash command experience with consistent autocomplete, richer help metadata, alias parity, and reliable keyboard navigation. Ensure future UI builders and help systems can pull deterministic command data from a single cached registry.

## Sprint Scope
- **Duration**: 5-7 days (40-56 hours)
- **Priority**: MEDIUM-HIGH (blocks improved command discoverability)
- **Dependencies**: None (independent sprint)

## Key Problems Addressed

### Current Issues Identified:
1. **Autocomplete Source Mismatch**: `AutocompleteController:14` reads legacy static `CommandRegistry` instead of dynamic `command_registry` table
2. **Missing Registry Metadata**: `command_registry` table lacks human-friendly fields (name, category, summary, usage, examples, aliases)
3. **Broken Alias Resolution**: Runtime controller only looks up canonical slugs, aliases like `/s` → `search` fail in DSL
4. **Static Help System**: `/help` uses hardcoded YAML instead of dynamic registry metadata
5. **Keyboard Navigation Issues**: TipTap suggestion list closes on arrow keys due to missing `preventDefault()`
6. **No Cache Invalidation**: Fresh commands don't appear without app restarts

## Task Breakdown

### DSL-UX-001: Enhanced Registry Schema & Metadata (8-12h)
- Extend `command_registry` migration with help metadata fields
- Update `CommandPackLoader::updateRegistryCache()` to persist manifest help data
- Add validation for help block requirements in YAML packs

### DSL-UX-002: Unified Autocomplete Service (10-14h) 
- Replace `AutocompleteController` static lookups with `command_registry` queries
- Implement alias-to-canonical mapping for autocomplete and runtime resolution
- Add cache busting integration with `frag:command:cache`

### DSL-UX-003: Dynamic Help System (8-12h)
- Replace static `/help` YAML with template rendering from registry metadata
- Create backing API (`GET /api/commands/help`) for reusable help data
- Implement long-lived cache (`command_help.index`) with auto-invalidation

### DSL-UX-004: Keyboard Navigation Fixes (4-6h)
- Fix TipTap `SlashCommandList` arrow key handling with proper event consumption
- Add client-side caching/debouncing to `fetchCommands` utility
- Create Storybook coverage for interaction behavior

### DSL-UX-005: Observability & Testing (6-8h)
- Add logging for cache rebuild outcomes and alias conflicts
- Emit metrics for autocomplete payload regeneration
- Create comprehensive test coverage (unit, feature, E2E)

### DSL-UX-006: Alias Conflict Resolution (4-6h)
- Implement conflict detection during cache rebuild
- Surface reserved vs user-authored pack warnings
- Add alias precedence rules and override protection

## Technical Architecture

### Enhanced Registry Schema:
```sql
ALTER TABLE command_registry ADD COLUMN name VARCHAR(255);
ALTER TABLE command_registry ADD COLUMN category VARCHAR(100);
ALTER TABLE command_registry ADD COLUMN summary TEXT;
ALTER TABLE command_registry ADD COLUMN usage TEXT;
ALTER TABLE command_registry ADD COLUMN examples JSON;
ALTER TABLE command_registry ADD COLUMN aliases JSON;
ALTER TABLE command_registry ADD COLUMN keywords JSON;
```

### Autocomplete Flow:
```
User types "/se" → AutocompleteService queries command_registry 
→ Joins cached manifest metadata → Expands aliases + triggers 
→ Returns enriched results with descriptions
```

### Help System Flow:
```
/help command → Template renders from registry metadata 
→ API endpoint serves reusable JSON → Cache invalidates on pack changes
```

## Expected Outcomes

### User Experience Improvements:
- **Consistent Autocomplete**: All DSL commands appear in suggestions immediately
- **Rich Help Metadata**: Descriptions, examples, and usage info in autocomplete
- **Alias Parity**: `/s`, `/j`, `/c` aliases work identically to full commands
- **Reliable Navigation**: Arrow keys work properly in command selection
- **Fresh Command Discovery**: New packs appear without app restart

### Technical Benefits:
- **Single Source of Truth**: All command metadata flows from `command_registry`
- **Cache Coherence**: Unified invalidation strategy across autocomplete and help
- **Conflict Detection**: Prevents silent alias overrides between packs
- **Performance**: Client-side caching reduces redundant API calls

## Success Criteria

### Functional:
- [ ] All DSL commands appear in autocomplete within 5 seconds of cache rebuild
- [ ] Aliases resolve to canonical commands in both autocomplete and execution
- [ ] Help system displays current registry state without hardcoded data
- [ ] Keyboard navigation works reliably (no popover closing on arrow keys)

### Technical:
- [ ] Zero alias conflicts logged during normal operation
- [ ] Cache rebuild metrics show consistent sub-second performance
- [ ] Test coverage >90% for autocomplete and help endpoints
- [ ] No autocomplete requests during typing bursts (debouncing works)

## Dependencies & Risks

### Dependencies:
- Current DSL command system (minimal changes to core execution)
- TipTap/React components for UI fixes
- Laravel cache system for invalidation strategy

### Risks:
- **Medium**: Complex alias resolution logic may need iteration
- **Low**: Help template migration requires careful data mapping
- **Low**: Keyboard event handling across different browsers

## Sprint Execution Notes

### Recommended Order:
1. **DSL-UX-001** (foundation - other tasks depend on schema)
2. **DSL-UX-002** (core functionality - enables testing)
3. **DSL-UX-003** + **DSL-UX-004** (can run in parallel)
4. **DSL-UX-005** + **DSL-UX-006** (final validation and polish)

### Testing Strategy:
- Unit tests for alias resolution and cache logic
- Feature tests for autocomplete API endpoints
- Cypress/Playwright for slash command interaction flows
- Manual testing on command discovery and keyboard navigation

This sprint transforms the DSL command system from static discovery to **dynamic, metadata-rich command experience** with reliable interaction patterns.
