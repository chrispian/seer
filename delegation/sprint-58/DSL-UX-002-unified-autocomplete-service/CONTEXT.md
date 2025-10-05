# DSL-UX-002: Unified Autocomplete Service - Context

## Current State Analysis

### Problematic Implementation: `AutocompleteController.php`
**Location**: `app/Http/Controllers/AutocompleteController.php:14`

```php
class AutocompleteController extends Controller
{
    public function commands(Request $request): JsonResponse
    {
        // PROBLEM: Uses legacy static CommandRegistry instead of database
        $commands = CommandRegistry::getAllCommands();
        
        // PROBLEM: Hardcoded descriptions, no metadata from manifests
        $commandData = collect($commands)->map(function ($command) {
            return [
                'slug' => $command['slug'],
                'description' => $command['description'] ?? 'No description available',
                'category' => $command['category'] ?? 'General',
            ];
        });

        return response()->json($commandData);
    }
}
```

**Issues Identified**:
1. **Static Source**: Uses `CommandRegistry::getAllCommands()` instead of `command_registry` table
2. **No Alias Support**: Only returns canonical slugs, missing alias-to-canonical mapping
3. **Limited Metadata**: Hardcoded descriptions, no usage examples or rich help content
4. **No Cache Integration**: Doesn't leverage `frag:command:cache` invalidation
5. **Poor Performance**: Rebuilds entire command list on every request

### Current Database Schema: `command_registry` Table
**From DSL-UX-001**, the enhanced schema will include:
```sql
command_registry:
- id (primary)
- slug (unique) -- canonical command identifier
- name           -- human-friendly display name  
- category       -- grouping for organization
- summary        -- one-line description
- usage          -- usage pattern template
- examples       -- JSON array of example usages
- aliases        -- JSON array of alternative slugs
- keywords       -- JSON array for search matching
- manifest_path  -- source YAML file path
- last_seen_at   -- cache invalidation timestamp
- created_at, updated_at
```

### Command Pack Loader Integration
**Location**: `app/Services/Commands/CommandPackLoader.php`

The `updateRegistryCache()` method already populates the `command_registry` table but **doesn't extract help metadata** from YAML manifests. We need to modify this to populate the new metadata fields.

### Frontend Autocomplete Consumer
**Location**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`

```typescript
// Current implementation fetches from /api/autocomplete/commands
const fetchCommands = async (query: string) => {
  const response = await fetch(`/api/autocomplete/commands?query=${query}`);
  return response.json();
};
```

**Problems**:
- No debouncing (fires on every keystroke)
- No client-side caching
- Doesn't handle alias resolution
- Limited to basic slug/description display

## Technical Dependencies

### Database Table
- **DSL-UX-001** must complete first to ensure enhanced schema exists
- New fields: `name`, `category`, `summary`, `usage`, `examples`, `aliases`, `keywords`

### Command Registry Population
- `CommandPackLoader::updateRegistryCache()` needs modification to extract help metadata
- YAML manifest parsing must include `help:` block extraction
- Validation for required help fields

### API Endpoint Design
**New Endpoint**: `GET /api/autocomplete/commands`
- Replace current static lookup with dynamic database queries
- Support alias-to-canonical resolution
- Include rich metadata in response
- Implement query filtering and ranking

### Service Layer Architecture
**New Class**: `app/Services/AutocompleteService.php`
- Centralize autocomplete logic
- Handle alias resolution
- Manage caching strategy
- Provide rich metadata formatting

## Integration Points

### Cache System
- **Read**: `frag:command:cache` for cache hit detection
- **Write**: Cache autocomplete payload for performance
- **Invalidate**: On command pack changes or registry updates

### Frontend Integration
- TipTap SlashCommand extension consumes autocomplete API
- Client-side debouncing and caching
- Rich metadata display in suggestion popover

### Command Execution Flow
```
User types "/se" → AutocompleteService.search("se") 
→ Queries command_registry with alias expansion
→ Returns enriched results with metadata
→ User selects suggestion → Resolves to canonical slug for execution
```

## Migration Strategy

### Phase 1: Service Layer
1. Create `AutocompleteService` class with registry queries
2. Implement alias resolution logic
3. Add caching and performance optimization

### Phase 2: Controller Update
1. Replace `AutocompleteController` static lookups
2. Integration with new service layer
3. Maintain API compatibility during transition

### Phase 3: Frontend Enhancement
1. Add client-side debouncing and caching
2. Rich metadata display in autocomplete
3. Improved keyboard navigation (handled in DSL-UX-004)

## Risk Mitigation

### Performance Concerns
- **Risk**: Database queries on every autocomplete request
- **Mitigation**: Multi-level caching (application cache + client cache)
- **Monitoring**: Track query performance and cache hit rates

### Alias Conflicts
- **Risk**: Multiple commands with same alias
- **Mitigation**: Conflict detection during cache rebuild (DSL-UX-006)
- **Fallback**: Precedence rules for conflict resolution

### Data Consistency
- **Risk**: Registry out of sync with manifest files
- **Mitigation**: Robust cache invalidation on pack changes
- **Validation**: Registry integrity checks during development

This context provides the foundation for implementing a unified autocomplete service that bridges static command discovery with dynamic, metadata-rich suggestions.