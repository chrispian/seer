# DSL-UX-003: Dynamic Help System - Implementation Plan

## Overview
Replace static help content with dynamic, registry-driven help generation that automatically stays synchronized with available commands.

**Dependencies**: DSL-UX-001 (Enhanced Registry Schema) must complete first  
**Estimated Time**: 8-12 hours  
**Priority**: HIGH (critical for help content accuracy)

## Implementation Phases

### Phase 1: HelpService Foundation (3-4 hours)

#### 1.1 Create HelpService Class
**File**: `app/Services/HelpService.php`

```php
<?php

namespace App\Services;

use App\Models\CommandRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HelpService
{
    public function generateHelp(?string $category = null, ?string $search = null, ?string $command = null): array
    {
        // Implementation details in Phase 1.2
    }
    
    public function getCategories(): Collection
    {
        // Get all command categories with counts
    }
    
    public function getCommandsByCategory(string $category): Collection
    {
        // Get commands filtered by category
    }
    
    public function searchHelp(string $query): Collection
    {
        // Search across command help content
    }
    
    public function getCommandHelp(string $slug): ?array
    {
        // Get detailed help for specific command
    }
    
    private function buildHelpIndex(): Collection
    {
        // Build cached help content index
    }
}
```

#### 1.2 Implement Core Help Generation
**Help Data Structure**:
```php
public function generateHelp(?string $category = null, ?string $search = null, ?string $command = null): array
{
    if ($command) {
        return $this->getCommandHelp($command);
    }
    
    if ($search) {
        return $this->formatSearchResults($this->searchHelp($search));
    }
    
    $commands = $category 
        ? $this->getCommandsByCategory($category)
        : $this->getAllCommandsGrouped();
    
    return [
        'overview' => $this->getOverview(),
        'categories' => $this->formatByCategory($commands),
        'meta' => $this->getMetadata()
    ];
}
```

#### 1.3 Implement Category Organization
```php
public function getCategories(): Collection
{
    return Cache::remember('help:categories', 3600, function() {
        return CommandRegistry::select('category')
            ->selectRaw('COUNT(*) as command_count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->get();
    });
}

private function formatByCategory(Collection $commands): array
{
    return $commands->groupBy('category')->map(function($categoryCommands, $categoryName) {
        return [
            'name' => $categoryName,
            'count' => $categoryCommands->count(),
            'commands' => $categoryCommands->map(function($command) {
                return [
                    'slug' => $command->slug,
                    'name' => $command->name,
                    'aliases' => json_decode($command->aliases ?? '[]'),
                    'summary' => $command->summary,
                    'usage' => $command->usage,
                    'examples' => json_decode($command->examples ?? '[]'),
                    'keywords' => json_decode($command->keywords ?? '[]')
                ];
            })->values()->toArray()
        ];
    })->values()->toArray();
}
```

### Phase 2: API Endpoint Development (2-3 hours)

#### 2.1 Create Help API Controller
**File**: `app/Http/Controllers/HelpController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\HelpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HelpController extends Controller
{
    public function __construct(
        private HelpService $helpService
    ) {}
    
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $search = $request->query('search');  
        $command = $request->query('command');
        $format = $request->query('format', 'json');
        
        $helpData = $this->helpService->generateHelp($category, $search, $command);
        
        if ($format === 'markdown') {
            return response()->json([
                'content' => $this->formatAsMarkdown($helpData),
                'format' => 'markdown'
            ]);
        }
        
        return response()->json(['help' => $helpData]);
    }
    
    private function formatAsMarkdown(array $helpData): string
    {
        // Convert help data to markdown format
        // Implementation in Phase 2.3
    }
}
```

#### 2.2 Add API Routes
**File**: `routes/api.php`

```php
// Add to existing routes
Route::get('/commands/help', [HelpController::class, 'index'])->name('api.commands.help');

// Optional: Category-specific routes
Route::get('/commands/help/categories', [HelpController::class, 'categories'])->name('api.commands.help.categories');
Route::get('/commands/help/search', [HelpController::class, 'search'])->name('api.commands.help.search');
```

#### 2.3 Response Formatting
**JSON Response Structure**:
```json
{
  "help": {
    "overview": {
      "total_commands": 25,
      "categories": [
        {"name": "Database", "count": 8},
        {"name": "AI", "count": 6},
        {"name": "Query", "count": 11}
      ],
      "last_updated": "2024-01-15T10:30:00Z"
    },
    "categories": [
      {
        "name": "Database",
        "count": 8,
        "commands": [
          {
            "slug": "search",
            "name": "Search Command",
            "aliases": ["/s", "/find"],
            "summary": "Search through fragments and resources",
            "usage": "/search <query> [--type=fragment]",
            "examples": [
              "/search react components",
              "/search --type=fragment authentication"
            ],
            "keywords": ["query", "lookup", "find"]
          }
        ]
      }
    ]
  },
  "meta": {
    "generated_at": "2024-01-15T10:30:15Z",
    "cache_key": "help:full:v1:20240115",
    "query_time_ms": 45,
    "cache_hit": true
  }
}
```

### Phase 3: Caching Strategy Implementation (2-3 hours)

#### 3.1 Multi-Level Caching
```php
class HelpService
{
    private function getCachedHelp(string $key, callable $generator, int $ttl = 3600): mixed
    {
        return Cache::remember($key, $ttl, $generator);
    }
    
    public function generateHelp(?string $category = null, ?string $search = null, ?string $command = null): array
    {
        // Full help cache (4 hours)
        if (!$category && !$search && !$command) {
            return $this->getCachedHelp('help:full:v1', function() {
                return $this->buildFullHelp();
            }, 14400);
        }
        
        // Category cache (2 hours)
        if ($category && !$search && !$command) {
            return $this->getCachedHelp("help:category:{$category}", function() use ($category) {
                return $this->buildCategoryHelp($category);
            }, 7200);
        }
        
        // Command-specific cache (1 hour)
        if ($command) {
            return $this->getCachedHelp("help:command:{$command}", function() use ($command) {
                return $this->buildCommandHelp($command);
            }, 3600);
        }
        
        // Search results not cached (too many variations)
        return $this->buildSearchHelp($search);
    }
}
```

#### 3.2 Cache Invalidation Integration
```php
// In HelpService or dedicated cache manager
public function invalidateHelpCache(): void
{
    $patterns = [
        'help:full:*',
        'help:category:*', 
        'help:command:*',
        'help:categories'
    ];
    
    foreach ($patterns as $pattern) {
        Cache::flush(); // Or use Redis pattern deletion
    }
    
    // Warm critical caches immediately
    $this->warmHelpCache();
}

private function warmHelpCache(): void
{
    // Pre-generate full help
    $this->generateHelp();
    
    // Pre-generate category listings
    $this->getCategories();
    
    // Pre-generate popular command help
    $popularCommands = ['search', 'ask', 'job', 'fragment'];
    foreach ($popularCommands as $command) {
        $this->getCommandHelp($command);
    }
}
```

#### 3.3 Event-Driven Cache Invalidation
**File**: `app/Listeners/InvalidateHelpCacheListener.php`

```php
<?php

namespace App\Listeners;

use App\Services\HelpService;
use Illuminate\Support\Facades\Log;

class InvalidateHelpCacheListener
{
    public function __construct(
        private HelpService $helpService
    ) {}
    
    public function handle($event): void
    {
        // Listen for command registry cache rebuild events
        if ($this->isCommandCacheEvent($event)) {
            Log::info('Invalidating help cache due to command registry update');
            $this->helpService->invalidateHelpCache();
        }
    }
    
    private function isCommandCacheEvent($event): bool
    {
        // Check if event relates to command registry changes
        return str_contains($event->cacheKey ?? '', 'frag:command:cache');
    }
}
```

### Phase 4: Command Handler Integration (2-3 hours)

#### 4.1 Locate and Update Help Command Handler
**Task**: Find existing help command handler (likely in `app/Services/Commands/` or similar)

**Current Implementation** (approximate):
```php
class HelpCommandHandler
{
    public function execute(CommandRequest $request): Response
    {
        // Returns static content from YAML or hardcoded strings
        return new Response($this->getStaticHelpContent());
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
        $parameters = $request->getParameters();
        $category = $parameters['category'] ?? null;
        $search = $parameters['search'] ?? null;
        $command = $parameters['command'] ?? null;
        
        // Extract from command input if no explicit parameters
        if (!$category && !$search && !$command) {
            $input = $request->getInput();
            if (preg_match('/^\/help\s+(\w+)/', $input, $matches)) {
                $firstArg = $matches[1];
                
                // Check if it's a category or command
                if ($this->helpService->isValidCategory($firstArg)) {
                    $category = $firstArg;
                } else {
                    $command = $firstArg;
                }
            }
        }
        
        $helpData = $this->helpService->generateHelp($category, $search, $command);
        
        return new Response($this->formatHelpForDisplay($helpData));
    }
    
    private function formatHelpForDisplay(array $helpData): string
    {
        // Convert help data to user-friendly display format
        // Could use Blade templates or markdown conversion
    }
}
```

#### 4.2 DSL Parser Enhancement
**Enhanced Help Command Syntax**:
```
/help                    → Show all categories
/help database          → Show database commands only
/help search            → Show help for search command  
/help --search=ai       → Search help content for "ai"
/help --category=query  → Show query commands
```

**Parser Updates** (location TBD):
```php
// In DSL command parser
if (preg_match('/^\/help(?:\s+(.+))?/', $input, $matches)) {
    $args = $matches[1] ?? '';
    
    $parameters = [];
    
    // Parse named parameters
    if (preg_match('/--search=([^\s]+)/', $args, $searchMatch)) {
        $parameters['search'] = $searchMatch[1];
    } elseif (preg_match('/--category=([^\s]+)/', $args, $categoryMatch)) {
        $parameters['category'] = $categoryMatch[1];
    } elseif (!empty(trim($args))) {
        // Simple argument (category or command name)
        $parameters['target'] = trim($args);
    }
    
    return new CommandRequest('help', $parameters, $input);
}
```

## Testing Strategy

### Unit Tests
**File**: `tests/Unit/HelpServiceTest.php`

```php
class HelpServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_generates_full_help_with_all_categories()
    public function test_filters_help_by_category()
    public function test_searches_help_content()
    public function test_gets_specific_command_help()
    public function test_caches_help_content_appropriately()
    public function test_invalidates_cache_on_registry_changes()
    public function test_handles_missing_commands_gracefully()
}
```

### Feature Tests
**File**: `tests/Feature/HelpApiTest.php`

```php
class HelpApiTest extends TestCase
{
    public function test_help_endpoint_returns_valid_structure()
    public function test_category_filtering_works()
    public function test_search_functionality()
    public function test_command_specific_help()
    public function test_response_includes_proper_metadata()
    public function test_cache_headers_set_correctly()
}
```

### Integration Tests
**File**: `tests/Feature/HelpCommandTest.php`

```php
class HelpCommandTest extends TestCase
{
    public function test_help_command_returns_dynamic_content()
    public function test_help_with_category_parameter()
    public function test_help_with_command_parameter()
    public function test_help_search_functionality()
    public function test_help_content_updates_with_registry()
}
```

## Performance Targets

### API Response Times
- **Full help**: < 200ms (cold cache), < 50ms (warm cache)
- **Category help**: < 150ms (cold cache), < 30ms (warm cache)  
- **Command help**: < 100ms (cold cache), < 20ms (warm cache)
- **Search help**: < 300ms (no cache due to query variations)

### Cache Performance
- **Hit ratio**: > 85% for help requests during normal usage
- **Cache size**: < 5MB for all help-related caches
- **Invalidation**: < 2 seconds for complete cache refresh
- **Warming**: < 10 seconds for critical cache pre-population

### Content Quality
- **Coverage**: 100% of commands have help metadata
- **Consistency**: Standardized format across all help content
- **Accuracy**: Help content matches actual command behavior
- **Freshness**: Help content updated within 5 minutes of registry changes

## Risk Mitigation

### Performance Degradation
- **Risk**: Dynamic help generation slower than static content
- **Mitigation**: Aggressive caching with 4-hour TTL for full help
- **Fallback**: Static help content as emergency backup
- **Monitoring**: Track help generation performance metrics

### Content Quality Issues
- **Risk**: Poor or inconsistent help content in registry
- **Mitigation**: Content validation rules and standards
- **Process**: Help content review during command development
- **Tooling**: Automated help content quality checks

### Cache Invalidation Failures
- **Risk**: Stale help content after registry updates
- **Mitigation**: Multiple invalidation triggers and monitoring
- **Recovery**: Manual cache clearing commands for admins
- **Testing**: Automated cache invalidation verification

## Success Criteria

### Functional Requirements
- [ ] Help content automatically reflects current command registry
- [ ] Category filtering works for all command categories
- [ ] Search functionality finds relevant commands
- [ ] Command-specific help shows complete information
- [ ] Help updates within 5 minutes of registry changes

### Performance Requirements
- [ ] API response times meet performance targets (95th percentile)
- [ ] Cache hit ratio > 85% during normal usage
- [ ] Help generation doesn't impact command execution performance
- [ ] Cache invalidation completes within 2 seconds

### Quality Requirements
- [ ] 100% of commands have complete help metadata
- [ ] Help content follows consistent formatting standards
- [ ] All help examples are valid and executable
- [ ] Help search returns relevant and accurate results

This comprehensive plan ensures the dynamic help system provides accurate, current, and performant help content that automatically stays synchronized with the command registry.