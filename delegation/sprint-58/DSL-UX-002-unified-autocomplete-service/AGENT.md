# DSL-UX-002: Unified Autocomplete Service

## Agent Role
Frontend-backend integration specialist focused on replacing static command lookups with dynamic registry-based autocomplete. Transform the autocomplete system to leverage rich metadata from the enhanced command registry.

## Objective
Replace the static `CommandRegistry::all()` lookups in `AutocompleteController` with dynamic queries against the `command_registry` table, enabling rich metadata display and alias resolution in command suggestions.

## Core Task
Create a unified autocomplete service that queries the enhanced command registry, resolves aliases to canonical commands, and provides rich metadata for enhanced user experience.

## Key Deliverables

### 1. Enhanced AutocompleteController
**File**: `app/Http/Controllers/AutocompleteController.php`
- Replace static command lookups with database queries
- Integrate rich metadata (summary, examples, categories)
- Implement alias expansion and canonical resolution
- Add cache invalidation integration

### 2. AutocompleteService Class
**File**: `app/Services/AutocompleteService.php`
- Centralized autocomplete logic for reusability
- Query optimization for fast response times
- Alias-to-canonical mapping functionality
- Cache layer for improved performance

### 3. Alias Resolution System
**File**: `app/Services/Commands/AliasResolver.php`
- Map aliases to canonical command slugs
- Handle both runtime execution and autocomplete scenarios
- Validate alias uniqueness and conflict detection
- Integration with CommandController for execution

### 4. Frontend Autocomplete Enhancements
**File**: `resources/js/islands/chat/tiptap/utils/autocomplete.ts`
- Client-side caching and debouncing
- Rich metadata display in suggestions
- Keyboard navigation improvements
- Error handling and fallbacks

## Success Criteria

### Backend:
- [ ] All DSL commands appear in autocomplete within 5 seconds of cache rebuild
- [ ] Aliases resolve correctly in both autocomplete and execution
- [ ] Rich metadata (summaries, categories) display in suggestions
- [ ] Response times under 100ms for typical autocomplete queries

### Frontend:
- [ ] Debounced requests reduce API calls during typing
- [ ] Rich command information displays in suggestion list
- [ ] Alias commands show canonical name with badge
- [ ] Graceful handling of API failures

### Integration:
- [ ] Cache invalidation properly refreshes autocomplete data
- [ ] Alias resolution works consistently across autocomplete and execution
- [ ] Performance remains acceptable with large command registries
