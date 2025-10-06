# DSL-UX-002: Unified Autocomplete Service - TODO

## Prerequisites
- [ ] **DSL-UX-001 Complete**: Enhanced registry schema with metadata fields
- [ ] **Database Migration**: New fields available in `command_registry` table
- [ ] **Registry Population**: `CommandPackLoader` extracting help metadata

## Phase 1: AutocompleteService Foundation (4-5 hours)

### 1.1 Create Service Class
- [ ] **Create** `app/Services/AutocompleteService.php`
- [ ] **Implement** constructor with dependency injection for `CommandRegistry` model
- [ ] **Add** service provider registration in `app/Providers/AppServiceProvider.php`
- [ ] **Create** service interface for testability (optional but recommended)

### 1.2 Implement Core Search Method
```php
public function search(string $query, int $limit = 10): Collection
{
    // TODO: Implement multi-step search logic
    // 1. Direct slug matches (highest priority)
    // 2. Alias matches with canonical resolution
    // 3. Name/summary fuzzy matches
    // 4. Keyword matches
    // 5. Apply ranking and limit
}
```

**Tasks**:
- [ ] **Query parsing**: Trim, lowercase, handle special characters
- [ ] **Direct matching**: Exact slug matches get highest priority
- [ ] **Fuzzy matching**: Use `LIKE` or `ILIKE` for partial matches
- [ ] **Ranking algorithm**: Score by match type and relevance
- [ ] **Metadata enrichment**: Include all registry fields in results
- [ ] **Performance optimization**: Limit database queries, use efficient joins

### 1.3 Implement Alias Resolution
```php
public function resolveAlias(string $alias): ?string
{
    // TODO: Alias-to-canonical slug resolution
}
```

**Tasks**:
- [ ] **Database query**: Search `aliases` JSON column for input
- [ ] **Caching**: Cache alias mappings for performance
- [ ] **Fallback logic**: Return null if no alias found
- [ ] **Validation**: Ensure canonical slug exists

### 1.4 Implement Caching Layer
```php
public function getCachedCommands(): Collection
{
    // TODO: Cache-first command retrieval
}

private function buildSearchIndex(): Collection
{
    // TODO: Build searchable command index
}
```

**Tasks**:
- [ ] **Query cache**: Cache individual search results (30 minutes TTL)
- [ ] **Index cache**: Cache full command index (4 hours TTL)
- [ ] **Cache keys**: Standardized naming convention
- [ ] **Invalidation**: Integration with `frag:command:cache` events
- [ ] **Warming**: Proactive cache warming after pack changes

## Phase 2: Controller Integration (3-4 hours)

### 2.1 Update AutocompleteController
**File**: `app/Http/Controllers/AutocompleteController.php`

**Tasks**:
- [ ] **Backup** existing implementation for rollback safety
- [ ] **Inject** `AutocompleteService` via constructor dependency injection
- [ ] **Replace** static `CommandRegistry::getAllCommands()` calls
- [ ] **Add** query parameter handling (`?query=`, `?limit=`, `?category=`)
- [ ] **Maintain** existing API contract for backward compatibility
- [ ] **Add** response metadata (query, total, cache_hit status)

### 2.2 Enhanced Response Format
```json
{
  "commands": [
    {
      "slug": "search",
      "name": "Search Command", 
      "category": "Query",
      "summary": "Search through fragments and resources",
      "usage": "/search <query> [--type=fragment]",
      "examples": ["..."],
      "aliases": ["s", "find"],
      "keywords": ["query", "lookup"]
    }
  ],
  "meta": {
    "query": "se",
    "total": 1,
    "cache_hit": true,
    "response_time_ms": 45
  }
}
```

**Tasks**:
- [ ] **Response transformer**: Convert registry models to API format
- [ ] **Metadata inclusion**: Add all enhanced fields from registry
- [ ] **Performance metrics**: Track and include response timing
- [ ] **Cache status**: Indicate cache hit/miss in response
- [ ] **Error handling**: Graceful degradation for service failures

### 2.3 API Route Updates
**File**: `routes/api.php`

**Tasks**:
- [ ] **Verify** existing route: `GET /api/autocomplete/commands`
- [ ] **Add** optional parameters: `query`, `limit`, `category`
- [ ] **Rate limiting**: Implement reasonable rate limits for autocomplete
- [ ] **Response headers**: Add cache control headers
- [ ] **Documentation**: Update API documentation for new response format

## Phase 3: Caching Strategy (2-3 hours)

### 3.1 Multi-Level Cache Implementation
**Tasks**:
- [ ] **Query cache**: Individual search result caching
  ```php
  $key = "autocomplete:query:" . md5($query . $limit);
  Cache::remember($key, 1800, /* search logic */);
  ```
- [ ] **Index cache**: Full command index caching
  ```php
  $key = "autocomplete:index";
  Cache::remember($key, 14400, /* build index */);
  ```
- [ ] **Alias cache**: Dedicated alias-to-canonical mapping cache
  ```php
  $key = "autocomplete:aliases";
  Cache::remember($key, 7200, /* build alias map */);
  ```

### 3.2 Cache Invalidation Integration
**Tasks**:
- [ ] **Event listener**: Listen for `frag:command:cache` invalidation events
- [ ] **Selective invalidation**: Clear related autocomplete caches
- [ ] **Batch operations**: Efficient cache clearing for multiple keys
- [ ] **Error handling**: Graceful fallback if cache clearing fails
- [ ] **Logging**: Track cache invalidation events and performance

### 3.3 Cache Monitoring
**Tasks**:
- [ ] **Metrics collection**: Hit/miss ratios, query times, cache sizes
- [ ] **Performance logging**: Slow query detection and alerting
- [ ] **Health checks**: Cache system health monitoring
- [ ] **Dashboard integration**: Expose cache metrics to monitoring systems

## Phase 4: Frontend Enhancement (2-3 hours)

### 4.1 Client-Side Debouncing
**File**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`

**Tasks**:
- [ ] **Install dependencies**: Ensure lodash debounce available
- [ ] **Implement debouncing**: 300ms delay on search queries
- [ ] **Query validation**: Skip API calls for queries < 2 characters
- [ ] **Request cancellation**: Cancel in-flight requests on new input
- [ ] **Loading states**: Show loading indicators during API calls

### 4.2 Client-Side Caching
**Tasks**:
- [ ] **Cache structure**: LRU cache implementation for query results
- [ ] **TTL management**: 5-minute expiration for cached results
- [ ] **Size limits**: Maximum 50 cached queries
- [ ] **Memory management**: Cleanup and garbage collection
- [ ] **Cache keys**: Consistent key generation for queries

### 4.3 Rich Metadata Display
**Tasks**:
- [ ] **Component updates**: Enhance suggestion rendering with metadata
- [ ] **CSS styling**: Attractive display for usage examples and categories
- [ ] **Truncation**: Handle long descriptions gracefully
- [ ] **Accessibility**: Proper ARIA labels and keyboard navigation
- [ ] **Responsive design**: Mobile-friendly autocomplete display

## Testing Implementation (throughout development)

### Unit Tests
**File**: `tests/Unit/AutocompleteServiceTest.php`

**Tasks**:
- [ ] **Test search method**: Various query types and edge cases
- [ ] **Test alias resolution**: Known aliases and error cases
- [ ] **Test caching**: Cache hit/miss scenarios
- [ ] **Test performance**: Response time benchmarks
- [ ] **Mock dependencies**: Database and cache layer mocking

### Feature Tests  
**File**: `tests/Feature/AutocompleteApiTest.php`

**Tasks**:
- [ ] **API endpoint testing**: Valid requests and responses
- [ ] **Parameter validation**: Query, limit, category parameters
- [ ] **Error handling**: Invalid requests and service failures
- [ ] **Performance testing**: Response time verification
- [ ] **Cache behavior**: Verify caching headers and behavior

### Frontend Tests
**File**: `resources/js/__tests__/SlashCommand.test.tsx`

**Tasks**:
- [ ] **Debouncing tests**: Verify 300ms delay behavior
- [ ] **Caching tests**: Client-side cache functionality
- [ ] **Rendering tests**: Metadata display components
- [ ] **Integration tests**: End-to-end autocomplete flow
- [ ] **Accessibility tests**: Keyboard navigation and screen readers

## Quality Assurance

### Code Review Checklist
- [ ] **Security**: No SQL injection or XSS vulnerabilities
- [ ] **Performance**: Database queries optimized with indexes
- [ ] **Error handling**: Graceful degradation and proper error messages
- [ ] **Logging**: Appropriate log levels and sensitive data protection
- [ ] **Documentation**: Code comments and API documentation updates

### Pre-Deployment Validation
- [ ] **Database migration**: Verify schema changes applied correctly
- [ ] **Cache system**: Confirm cache invalidation works end-to-end
- [ ] **API compatibility**: Backward compatibility maintained
- [ ] **Frontend integration**: Autocomplete works in all browsers
- [ ] **Performance benchmarks**: Meet or exceed performance targets

## Success Metrics Validation

### Functional Validation
- [ ] **Search accuracy**: Relevant commands appear in autocomplete
- [ ] **Alias resolution**: All registered aliases resolve correctly
- [ ] **Rich metadata**: Complete information displays in suggestions
- [ ] **Response time**: API responds within performance targets
- [ ] **Cache effectiveness**: Hit ratios meet minimum thresholds

### Performance Validation
- [ ] **API latency**: < 200ms for 95th percentile requests
- [ ] **Database performance**: Query execution times under 100ms
- [ ] **Cache performance**: Hit ratio > 80% during normal usage
- [ ] **Frontend responsiveness**: Debouncing reduces API calls by > 70%
- [ ] **Memory usage**: Autocomplete caches under 10MB total

### User Experience Validation
- [ ] **Discovery**: All commands findable within 5 seconds of typing
- [ ] **Accuracy**: Search results match user intent
- [ ] **Performance**: No noticeable delays in autocomplete
- [ ] **Reliability**: Consistent behavior across all command types
- [ ] **Metadata**: Helpful information aids command selection

## Deployment Steps

1. [ ] **Database migration**: Deploy schema changes to production
2. [ ] **Service deployment**: Deploy AutocompleteService with feature flags
3. [ ] **Cache warming**: Populate caches with initial data
4. [ ] **Controller update**: Switch to new service implementation
5. [ ] **Frontend deployment**: Deploy enhanced autocomplete components
6. [ ] **Monitoring setup**: Enable performance and error monitoring
7. [ ] **Gradual rollout**: Feature flag rollout to user segments
8. [ ] **Full deployment**: Remove feature flags after validation

This comprehensive TODO list ensures systematic implementation of the unified autocomplete service with proper testing, monitoring, and deployment practices.