# DSL-UX-003: Dynamic Help System - TODO

## Prerequisites
- [ ] **DSL-UX-001 Complete**: Enhanced registry schema with help metadata fields
- [ ] **Registry Populated**: Command metadata available in `command_registry` table
- [ ] **Locate Current Help**: Identify existing help command implementation

## Pre-Implementation Research (1 hour)

### Current System Analysis
- [ ] **Find help command handler**: Search codebase for help command implementation
  ```bash
  grep -r "help" app/Services/Commands/ app/Actions/Commands/
  grep -r "HelpCommand" app/
  grep -r "/help" fragments/commands/
  ```
- [ ] **Identify static content**: Locate current help content files (YAML, JSON, etc.)
- [ ] **Map execution flow**: Trace how `/help` command currently executes
- [ ] **Check existing routes**: Verify if help API endpoints already exist
- [ ] **Review DSL parser**: Understand how help commands are parsed

## Phase 1: HelpService Foundation (3-4 hours)

### 1.1 Create HelpService Class (1 hour)
- [ ] **Create** `app/Services/HelpService.php`
- [ ] **Add constructor** with `CommandRegistry` model injection
- [ ] **Register service** in `app/Providers/AppServiceProvider.php` if needed
- [ ] **Create interface** `app/Contracts/HelpServiceInterface.php` (optional)

### 1.2 Implement Core Methods (2 hours)
```php
public function generateHelp(?string $category = null, ?string $search = null, ?string $command = null): array
{
    // TODO: Main help generation logic
}
```

**Tasks**:
- [ ] **Parameter routing**: Route to specific methods based on parameters
- [ ] **Full help generation**: When no parameters provided
- [ ] **Category filtering**: Filter commands by category
- [ ] **Command lookup**: Get help for specific command
- [ ] **Search functionality**: Search across help content
- [ ] **Error handling**: Graceful handling of invalid inputs

```php
public function getCategories(): Collection
{
    // TODO: Get all categories with command counts
}
```

**Tasks**:
- [ ] **Query implementation**: GROUP BY category with counts
- [ ] **Sorting**: Alphabetical category ordering
- [ ] **Caching**: Cache category list for performance
- [ ] **Null handling**: Handle commands without categories

```php
public function getCommandsByCategory(string $category): Collection  
{
    // TODO: Get commands filtered by category
}
```

**Tasks**:
- [ ] **Validation**: Check if category exists
- [ ] **Query optimization**: Efficient category filtering
- [ ] **Metadata inclusion**: Include all help fields
- [ ] **Sorting**: Order commands within category

```php
public function searchHelp(string $query): Collection
{
    // TODO: Search across command help content
}
```

**Tasks**:
- [ ] **Multi-field search**: Search name, summary, keywords, examples
- [ ] **Ranking algorithm**: Relevance scoring for results
- [ ] **Query processing**: Handle partial matches and synonyms
- [ ] **Performance**: Limit and optimize search queries

### 1.3 Data Formatting Methods (1 hour)
```php
private function formatByCategory(Collection $commands): array
{
    // TODO: Group commands by category with proper structure
}

private function getOverview(): array
{
    // TODO: Generate help overview with stats
}

private function getMetadata(): array
{
    // TODO: Add response metadata (timestamps, cache info)
}
```

**Tasks**:
- [ ] **Category grouping**: Organize commands by category
- [ ] **Command formatting**: Transform registry models to help format
- [ ] **JSON handling**: Properly decode JSON fields (aliases, examples)
- [ ] **Overview stats**: Total commands, categories, last updated
- [ ] **Metadata**: Generation timestamps, cache keys, version info

## Phase 2: API Endpoint Development (2-3 hours)

### 2.1 Create HelpController (1 hour)
- [ ] **Create** `app/Http/Controllers/HelpController.php`
- [ ] **Add constructor** with `HelpService` dependency injection
- [ ] **Implement** `index()` method for main help endpoint
- [ ] **Add** parameter validation and error handling
- [ ] **Include** response formatting for different output types

### 2.2 Implement API Methods (1 hour)
```php
public function index(Request $request): JsonResponse
{
    // TODO: Main help API endpoint
}
```

**Tasks**:
- [ ] **Parameter extraction**: Get category, search, command, format from request
- [ ] **Validation**: Validate parameter values and combinations
- [ ] **Service integration**: Call HelpService with validated parameters
- [ ] **Response formatting**: Format for JSON/markdown output
- [ ] **Error handling**: Return appropriate HTTP status codes
- [ ] **Headers**: Set cache control and content type headers

```php
public function categories(Request $request): JsonResponse
{
    // TODO: Categories endpoint (optional)
}

public function search(Request $request): JsonResponse  
{
    // TODO: Dedicated search endpoint (optional)
}
```

### 2.3 Add API Routes (30 minutes)
**File**: `routes/api.php`

**Tasks**:
- [ ] **Add main route**: `GET /api/commands/help`
- [ ] **Add optional routes**: Categories and search endpoints
- [ ] **Set middleware**: Rate limiting and authentication if needed
- [ ] **Route naming**: Use consistent naming convention
- [ ] **Documentation**: Add route documentation/comments

### 2.4 Response Format Implementation (30 minutes)
**Tasks**:
- [ ] **JSON structure**: Implement standard help response format
- [ ] **Markdown conversion**: Convert help data to markdown format
- [ ] **HTML rendering**: Optional HTML format support
- [ ] **Metadata inclusion**: Add generation timestamps and cache info
- [ ] **Error responses**: Standard error response format

## Phase 3: Caching Strategy Implementation (2-3 hours)

### 3.1 Basic Caching Implementation (1 hour)
```php
private function getCachedHelp(string $key, callable $generator, int $ttl = 3600): mixed
{
    // TODO: Generic cache helper method
}
```

**Tasks**:
- [ ] **Cache helper**: Create reusable caching method
- [ ] **Key generation**: Standardized cache key naming
- [ ] **TTL management**: Different TTLs for different content types
- [ ] **Cache tags**: Use cache tags for easier invalidation (if Redis)
- [ ] **Error handling**: Fallback when cache fails

### 3.2 Multi-Level Caching (1 hour)
**Cache Levels**:
- [ ] **Full help cache**: 4-hour TTL, key: `help:full:v1`
- [ ] **Category cache**: 2-hour TTL, key: `help:category:{category}`
- [ ] **Command cache**: 1-hour TTL, key: `help:command:{slug}`
- [ ] **Categories list**: 1-hour TTL, key: `help:categories`

**Implementation Tasks**:
- [ ] **Cache strategy**: Implement different caching for each content type
- [ ] **Key management**: Consistent key naming and versioning
- [ ] **Conditional caching**: Don't cache search results (too many variations)
- [ ] **Performance monitoring**: Track cache hit/miss ratios

### 3.3 Cache Invalidation System (1 hour)
```php
public function invalidateHelpCache(): void
{
    // TODO: Clear all help-related caches
}

private function warmHelpCache(): void
{
    // TODO: Pre-populate critical caches
}
```

**Tasks**:
- [ ] **Pattern-based clearing**: Clear caches by pattern/prefix
- [ ] **Selective invalidation**: Only clear affected cache levels
- [ ] **Cache warming**: Proactively regenerate critical caches
- [ ] **Error handling**: Continue if some cache clearing fails
- [ ] **Logging**: Log cache invalidation events for monitoring

### 3.4 Event Integration (30 minutes)
**File**: `app/Listeners/InvalidateHelpCacheListener.php`

**Tasks**:
- [ ] **Create listener**: Listen for command registry cache events
- [ ] **Event detection**: Identify registry update events
- [ ] **Integration**: Connect listener to help cache invalidation
- [ ] **Register listener**: Add to EventServiceProvider
- [ ] **Error handling**: Handle listener failures gracefully

## Phase 4: Command Handler Integration (2-3 hours)

### 4.1 Locate Current Implementation (30 minutes)
**Research Tasks**:
- [ ] **Find handler**: Locate existing help command handler class
- [ ] **Understand flow**: Map current execution path
- [ ] **Identify interface**: Understand CommandRequest/Response structure
- [ ] **Check parser**: How help commands are parsed from user input
- [ ] **Note dependencies**: What the current handler depends on

### 4.2 Update Help Command Handler (1-2 hours)
**Current Implementation** (approximate location):
```php
// Find actual location: app/Services/Commands/HelpCommandHandler.php or similar
class HelpCommandHandler
{
    public function execute(CommandRequest $request): Response
    {
        // TODO: Replace static content with dynamic generation
    }
}
```

**Tasks**:
- [ ] **Backup current**: Save existing implementation for rollback
- [ ] **Add dependency**: Inject HelpService into constructor
- [ ] **Parse parameters**: Extract category/search/command from request
- [ ] **Service integration**: Call HelpService with extracted parameters
- [ ] **Format response**: Convert help data to user-friendly display
- [ ] **Error handling**: Handle service failures gracefully
- [ ] **Maintain compatibility**: Ensure existing behavior preserved

### 4.3 Parameter Parsing Enhancement (30 minutes)
**Enhanced Help Syntax**:
```
/help                    → Show all categories
/help database          → Show database commands only
/help search            → Show help for search command
/help --search=ai       → Search help content for "ai"
/help --category=query  → Show query commands
```

**Tasks**:
- [ ] **Parse arguments**: Extract first argument as category/command
- [ ] **Named parameters**: Support --search= and --category= syntax
- [ ] **Validation**: Check if argument is valid category/command
- [ ] **Disambiguation**: Handle conflicts between category and command names
- [ ] **Error messages**: Helpful errors for invalid syntax

### 4.4 Display Formatting (1 hour)
```php
private function formatHelpForDisplay(array $helpData): string
{
    // TODO: Convert help data to user-friendly format
}
```

**Tasks**:
- [ ] **Template choice**: Use Blade templates or markdown conversion
- [ ] **Category sections**: Format categories with headers
- [ ] **Command formatting**: Display name, aliases, summary, usage, examples
- [ ] **Mobile-friendly**: Ensure help displays well on all devices
- [ ] **Styling**: Apply consistent styling for help content
- [ ] **Length management**: Handle long help content gracefully

## Testing Implementation (throughout development)

### Unit Tests (2 hours)
**File**: `tests/Unit/HelpServiceTest.php`

**Test Cases**:
- [ ] **Full help generation**: Test complete help output structure
- [ ] **Category filtering**: Test category-specific help
- [ ] **Command lookup**: Test individual command help
- [ ] **Search functionality**: Test help content search
- [ ] **Cache behavior**: Test caching and invalidation
- [ ] **Error handling**: Test invalid inputs and edge cases
- [ ] **Performance**: Test response times meet targets

**Implementation Tasks**:
- [ ] **Test database**: Use RefreshDatabase trait
- [ ] **Sample data**: Create test commands with help metadata
- [ ] **Mock caching**: Test cache behavior with mocked cache
- [ ] **Assertions**: Verify help structure and content accuracy
- [ ] **Edge cases**: Test empty categories, missing commands, etc.

### Feature Tests (1 hour)
**File**: `tests/Feature/HelpApiTest.php`

**Test Cases**:
- [ ] **API endpoints**: Test all help API endpoints
- [ ] **Response structure**: Verify JSON response format
- [ ] **Parameter handling**: Test query parameters work correctly
- [ ] **Error responses**: Test error conditions return proper HTTP codes
- [ ] **Cache headers**: Verify cache control headers set correctly
- [ ] **Performance**: Test API response times

### Integration Tests (1 hour)
**File**: `tests/Feature/HelpCommandTest.php`

**Test Cases**:
- [ ] **Command execution**: Test help command through DSL parser
- [ ] **Parameter parsing**: Test various help command syntaxes
- [ ] **Dynamic content**: Verify help shows current registry data
- [ ] **Cache integration**: Test help updates when registry changes
- [ ] **Display formatting**: Test help content displays correctly

## Quality Assurance

### Code Review Checklist
- [ ] **Security**: No sensitive data exposed in help content
- [ ] **Performance**: Database queries optimized and cached
- [ ] **Error handling**: Graceful degradation for all failure modes
- [ ] **Logging**: Appropriate logging for debugging and monitoring
- [ ] **Documentation**: Clear code comments and API documentation

### Performance Validation
- [ ] **Response times**: Meet performance targets for all help types
- [ ] **Cache effectiveness**: Achieve minimum cache hit ratios
- [ ] **Memory usage**: Help caches stay within reasonable limits
- [ ] **Database impact**: Help queries don't impact other operations
- [ ] **Scaling**: Performance maintained under concurrent help requests

### Content Quality Validation
- [ ] **Completeness**: All commands have required help metadata
- [ ] **Accuracy**: Help content matches actual command behavior
- [ ] **Consistency**: Standardized format across all help content
- [ ] **Examples**: All usage examples are valid and executable
- [ ] **Search relevance**: Search returns relevant and useful results

## Pre-Deployment Checklist

### Infrastructure
- [ ] **Database migration**: Registry schema includes help metadata
- [ ] **Cache configuration**: Cache system properly configured
- [ ] **Event listeners**: Help cache invalidation listeners registered
- [ ] **API routes**: Help endpoints deployed and accessible
- [ ] **Monitoring**: Performance and error monitoring in place

### Content Validation
- [ ] **Registry population**: All commands have help metadata
- [ ] **Content review**: Help content reviewed for quality and accuracy
- [ ] **Link validation**: Any links in help content are valid
- [ ] **Example testing**: All usage examples tested and working
- [ ] **Category organization**: Commands properly categorized

### Testing
- [ ] **All tests pass**: Unit, feature, and integration tests pass
- [ ] **Performance tests**: Response times meet requirements
- [ ] **Cache tests**: Cache invalidation working correctly
- [ ] **End-to-end tests**: Help command works through full user flow
- [ ] **Cross-browser**: Help display works in all supported browsers

## Success Metrics Validation

### Functional Validation
- [ ] **Dynamic content**: Help reflects current command registry
- [ ] **Real-time updates**: Help updates within 5 minutes of registry changes
- [ ] **Search accuracy**: Search returns relevant commands for queries
- [ ] **Complete coverage**: All commands findable through help system
- [ ] **Parameter handling**: All help command syntaxes work correctly

### Performance Validation
- [ ] **API performance**: Response times under 200ms (95th percentile)
- [ ] **Cache performance**: Hit ratio > 85% during normal usage
- [ ] **Search performance**: Search completes under 300ms
- [ ] **Memory usage**: Help caches under 5MB total
- [ ] **Concurrent handling**: Performance maintained under load

### User Experience Validation
- [ ] **Content quality**: Help content helpful and accurate
- [ ] **Navigation**: Easy to find specific commands or categories
- [ ] **Formatting**: Help displays clearly on all device types
- [ ] **Search utility**: Users can find commands through search
- [ ] **Performance perception**: Help feels fast and responsive

This comprehensive TODO ensures systematic implementation of the dynamic help system with proper quality assurance and performance validation.