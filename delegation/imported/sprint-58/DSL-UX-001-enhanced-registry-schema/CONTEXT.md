# Context: Enhanced Registry Schema & Metadata

## Current System State

### Existing Registry Schema
The `command_registry` table currently tracks basic command information:
- `id`, `slug`, `version`, `source_path`
- `steps_hash`, `capabilities`, `requires_secrets`, `reserved`
- Limited to technical metadata, no user-facing information

### Static Command Descriptions
**File**: `app/Http/Controllers/AutocompleteController.php:108-118`
```php
private function getCommandDescription(string $command): string
{
    $descriptions = [
        'session' => 'Start or manage chat sessions',
        'recall' => 'Recall saved fragments and todos',
        'bookmark' => 'Save and manage bookmarks',
        'help' => 'Show available commands',
        'clear' => 'Clear chat history',
        'frag' => 'Create fragment from text',
    ];
    return $descriptions[$command] ?? 'Execute command';
}
```
**Problem**: Hardcoded descriptions don't include DSL commands from YAML packs.

### Command Pack Loading Process
**File**: `app/Services/Commands/CommandPackLoader.php:135-150`
```php
protected function updateRegistryCache(string $slug, array $commandPack, string $sourcePath): void
{
    $stepsHash = $this->calculateStepsHash($commandPack);
    $capabilities = $this->extractCapabilities($commandPack);
    $requiresSecrets = $this->extractRequiredSecrets($commandPack);
    $reserved = $this->isReservedCommand($commandPack);

    CommandRegistry::updateOrCreateEntry($slug, [
        'version' => $commandPack['manifest']['version'] ?? '1.0.0',
        'source_path' => $sourcePath,
        'steps_hash' => $stepsHash,
        'capabilities' => $capabilities,
        'requires_secrets' => $requiresSecrets,
        'reserved' => $reserved,
    ]);
}
```
**Gap**: No extraction or persistence of help metadata from manifests.

## Command Manifest Examples

### Current Manifest Structure
**File**: `fragments/commands/search/command.yaml`
```yaml
name: "Unified Search"
slug: search
version: 3.0.0
triggers:
  slash: "/search"
  aliases: ["/s"]
  input_mode: "inline"

requires:
  secrets: []
  capabilities: []
```
**Missing**: Help block with user-facing metadata.

### Desired Help Block Structure
```yaml
help:
  name: "Search Command"
  category: "search"
  summary: "Search through fragments, todos, and content"
  usage: "/search <query> [filters]"
  examples:
    - "/search meeting notes"
    - "/search #urgent todo"
    - "/search type:fragment typescript"
  keywords: ["find", "lookup", "query", "discover", "locate"]
```

## Legacy vs DSL Command Systems

### Static CommandRegistry (Legacy)
**File**: `app/Services/CommandRegistry.php:25-56`
```php
protected static array $commands = [
    'recall' => RecallCommand::class,
    'todo' => TodoCommand::class,
    't' => TodoCommand::class, // alias
    'vault' => VaultCommand::class,
    'v' => VaultCommand::class, // alias
    // ... more hardcoded entries
];
```
**Status**: Being phased out in favor of DSL YAML packs.

### DSL Command System (Current)
- Commands defined in `fragments/commands/*/command.yaml`
- Loaded via `CommandPackLoader`
- Stored in `command_registry` table
- Executed via `CommandRunner`

## Database Schema Requirements

### Performance Considerations
- **Command Lookup**: Frequent queries by slug and aliases
- **Category Filtering**: Group commands for help display
- **Search Functionality**: Full-text search through names, summaries, keywords

### Index Strategy
```sql
INDEX(slug, version)     -- Existing primary lookup
INDEX(category)          -- Group filtering
INDEX(name)             -- Name-based search
FULLTEXT(summary, keywords) -- Content search (future)
```

### JSON Field Handling
- `aliases`: Array of alternative command triggers
- `examples`: Array of usage examples with descriptions
- `keywords`: Array of searchable terms for discovery

## Integration Points

### AutocompleteController Enhancement
**Current**: Reads from static `CommandRegistry::all()`
**Future**: Queries `command_registry` table with rich metadata

### Help System Evolution
**Current**: Static YAML template in `fragments/commands/help/command.yaml`
**Future**: Dynamic template rendering from registry metadata

### Command Resolution
**Current**: Only canonical slug lookup
**Future**: Alias-to-canonical mapping for seamless resolution

## Migration Considerations

### Backward Compatibility
- Existing registry entries must continue working
- Commands without help blocks should have sensible defaults
- No breaking changes to current pack loading

### Data Population Strategy
- New help metadata fields start as nullable
- Existing packs can be gradually enhanced with help blocks
- Fallback to manifest name/slug for missing metadata

### Performance Impact
- Additional columns increase row size marginally
- JSON field storage requires careful indexing strategy
- Query patterns need optimization for autocomplete performance

## Validation Framework

### Help Block Validation Rules
```yaml
help:
  name:        required|string|max:255
  category:    optional|string|in:search,fragment,session,system,utility
  summary:     required_unless:reserved,true|string|max:500
  usage:       optional|string|max:300
  examples:    optional|array|max:5
  keywords:    optional|array|max:10
```

### Error Handling Strategy
- **Validation Failures**: Log warnings, continue with minimal metadata
- **Schema Errors**: Graceful degradation to existing fields
- **JSON Malformation**: Skip invalid JSON, preserve valid fields

This context establishes the foundation for transforming the command registry from a technical metadata store into a comprehensive command discovery and documentation system.
