# DSL-UX-003: Dynamic Help System - Context

## Current State Analysis

### Static Help Implementation Problem
**Current Issue**: The `/help` command uses hardcoded YAML content instead of dynamic registry data.

**Location**: Help content likely stored in static files (need to identify exact location)
- Possible locations: `resources/views/help.yaml`, `fragments/commands/help.yaml`, or similar
- **Problem**: Static content becomes stale when new commands are added
- **Problem**: No single source of truth between help content and actual command registry
- **Problem**: Manual maintenance required for help documentation

### Command Registry as Source of Truth
**From DSL-UX-001**, the enhanced `command_registry` table includes:
```sql
command_registry:
- slug           -- canonical command identifier
- name           -- human-friendly display name
- category       -- grouping for organization (Database, AI, Query, etc.)
- summary        -- one-line description for help overview
- usage          -- usage pattern template ("/search <query> [--type]")
- examples       -- JSON array of real usage examples
- aliases        -- JSON array of alternative slugs (["/s", "/find"])
- keywords       -- JSON array for improved help search
- manifest_path  -- source YAML file for reference
```

This registry should be the **single source of truth** for all help content generation.

### Current Help Command Execution
**Likely Flow** (need to verify):
```
User types "/help" → DSL parser recognizes command 
→ Executes help command handler → Returns static content
```

**New Target Flow**:
```
User types "/help" → DSL parser recognizes command
→ Queries command_registry table → Generates dynamic help content
→ Templates and formats response → Returns current registry state
```

### Help Content Structure Requirements

#### Overview Section
- List of available command categories
- Total command count
- Quick usage instructions

#### Category-Based Organization
```markdown
## Database Commands
- **search** (`/search`, `/s`) - Search through fragments and resources
  Usage: `/search <query> [--type=fragment]`
  Example: `/search react components`

- **job** (`/j`) - Execute database operations and jobs
  Usage: `/job <operation> [parameters]`
  Example: `/job migrate --fresh`

## AI Commands  
- **ask** (`/ask`, `/a`) - Query AI providers with context
  Usage: `/ask <question> [--provider=openai]`
  Example: `/ask explain this function --provider=claude`
```

#### Command Detail Format
For each command, help should include:
- **Name**: Human-readable command name
- **Aliases**: All available shortcuts
- **Category**: Logical grouping
- **Summary**: One-line description
- **Usage Pattern**: Syntax template with parameters
- **Examples**: Real usage examples from registry
- **Keywords**: For help search functionality

## Technical Architecture Requirements

### API Endpoint Design
**New Endpoint**: `GET /api/commands/help`

**Query Parameters**:
- `?category=database` - Filter by category
- `?search=fragment` - Search help content
- `?format=json|markdown|html` - Response format
- `?command=search` - Get help for specific command

**Response Format**:
```json
{
  "help": {
    "overview": {
      "total_commands": 25,
      "categories": ["Database", "AI", "Query", "System"],
      "last_updated": "2024-01-15T10:30:00Z"
    },
    "categories": [
      {
        "name": "Database",
        "commands": [
          {
            "slug": "search",
            "name": "Search Command",
            "aliases": ["/s", "/find"],
            "summary": "Search through fragments and resources",
            "usage": "/search <query> [--type=fragment]",
            "examples": ["/search react components", "/search --type=fragment auth"]
          }
        ]
      }
    ]
  },
  "meta": {
    "generated_at": "2024-01-15T10:30:15Z",
    "cache_key": "help:v1:20240115",
    "registry_version": "abc123"
  }
}
```

### Template System Integration
**Template Location**: `resources/views/help/dynamic.blade.php`

**Template Variables**:
```php
@php
$helpData = $apiResponse['help'];
$categories = $helpData['categories'];
@endphp

<div class="help-content">
  <h1>Command Help ({{ $helpData['overview']['total_commands'] }} commands)</h1>
  
  @foreach($categories as $category)
    <h2>{{ $category['name'] }} Commands</h2>
    @foreach($category['commands'] as $command)
      <div class="command-help">
        <h3>{{ $command['name'] }}
          @if($command['aliases'])
            <span class="aliases">({{ implode(', ', $command['aliases']) }})</span>
          @endif
        </h3>
        <p>{{ $command['summary'] }}</p>
        <code>{{ $command['usage'] }}</code>
        @if($command['examples'])
          <div class="examples">
            @foreach($command['examples'] as $example)
              <code>{{ $example }}</code>
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
  @endforeach
</div>
```

### Cache Strategy
**Cache Layers**:
1. **Full Help Content**: 4-hour cache, invalidated on registry changes
2. **Category Sections**: 2-hour cache, allows partial updates
3. **Individual Commands**: 1-hour cache, for detailed command help

**Cache Keys**:
```php
"help:full:v1"           // Complete help content
"help:category:database" // Category-specific content  
"help:command:search"    // Individual command help
"help:search:fragment"   // Search result cache
```

**Invalidation Strategy**:
- **Trigger**: Registry cache rebuild events (`frag:command:cache`)
- **Scope**: Clear all help-related cache keys
- **Warming**: Proactively regenerate help content after cache clear

## Integration Points

### Command Handler Integration
**Current Handler** (need to locate):
```php
// Likely in app/Services/Commands/ or similar
class HelpCommandHandler
{
    public function execute(CommandRequest $request): Response
    {
        // Currently returns static content
        // Need to replace with dynamic generation
    }
}
```

**New Implementation**:
```php
class HelpCommandHandler  
{
    public function __construct(
        private HelpService $helpService
    ) {}
    
    public function execute(CommandRequest $request): Response
    {
        $category = $request->getParameter('category');
        $search = $request->getParameter('search');
        $command = $request->getParameter('command');
        
        return $this->helpService->generateHelp($category, $search, $command);
    }
}
```

### DSL Parser Integration
**Help Command Recognition**:
```
/help              → Show all categories
/help database     → Show database commands only  
/help search       → Show help for search command
/help --search=ai  → Search help content for "ai"
```

### Frontend Help Display
**Help Modal/Panel**:
- Triggered by `/help` command execution
- Displays formatted help content
- Supports category navigation
- Includes search functionality within help
- Responsive design for mobile devices

## Migration Strategy

### Phase 1: API Development
1. Create `HelpService` class with registry queries
2. Build `GET /api/commands/help` endpoint
3. Implement caching and performance optimization

### Phase 2: Template System
1. Create dynamic help templates
2. Integrate with existing command execution flow
3. Maintain backward compatibility during transition

### Phase 3: Command Handler Update
1. Replace static help handler with dynamic version
2. Add parameter support for filtering and search
3. Update DSL parser for enhanced help syntax

### Phase 4: Frontend Enhancement
1. Update help display components
2. Add interactive help navigation
3. Implement client-side help search

## Data Requirements

### Registry Data Quality
**Required Fields** (from DSL-UX-001):
- Every command must have `name`, `category`, `summary`
- `usage` pattern should follow consistent format
- `examples` should include real, working examples
- `aliases` should be comprehensive and accurate

### Content Standards
**Help Content Guidelines**:
- **Summaries**: Clear, one-line descriptions under 100 characters
- **Usage Patterns**: Consistent syntax with `<required>` and `[optional]` parameters
- **Examples**: Real examples that users can copy and execute
- **Categories**: Logical groupings that aid discovery

### Validation Requirements
**Registry Validation** (can be part of DSL-UX-001):
- Ensure required help fields are populated
- Validate usage pattern syntax
- Check example command validity
- Verify category consistency

## Risk Assessment

### Performance Concerns
- **Risk**: Dynamic help generation slower than static content
- **Mitigation**: Aggressive caching strategy with 4-hour TTL
- **Monitoring**: Track help generation performance

### Content Quality
- **Risk**: Inconsistent or poor quality help content in registry
- **Mitigation**: Validation rules and content standards
- **Process**: Review help content during command pack development

### Cache Coherence
- **Risk**: Stale help content despite registry updates
- **Mitigation**: Event-driven cache invalidation
- **Testing**: Automated tests for cache invalidation scenarios

This context establishes the foundation for transforming static help content into a dynamic, registry-driven system that stays automatically synchronized with available commands.