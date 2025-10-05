# DSL-UX-002: Unified Autocomplete Service - Implementation Plan

## Overview
Transform static autocomplete lookups into a dynamic, database-driven service with alias resolution and rich metadata support.

**Dependencies**: DSL-UX-001 (Enhanced Registry Schema) must complete first  
**Estimated Time**: 10-14 hours  
**Priority**: HIGH (core functionality)

## Implementation Phases

### Phase 1: AutocompleteService Foundation (4-5 hours)

#### 1.1 Create Service Class
**File**: `app/Services/AutocompleteService.php`

```php
<?php

namespace App\Services;

use App\Models\CommandRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AutocompleteService
{
    public function search(string $query, int $limit = 10): Collection
    {
        // Implementation details in TODO.md
    }
    
    public function resolveAlias(string $alias): ?string
    {
        // Alias-to-canonical resolution
    }
    
    public function getCachedCommands(): Collection
    {
        // Cache-first command retrieval
    }
    
    private function buildSearchIndex(): Collection
    {
        // Build searchable command index with metadata
    }
}
```

#### 1.2 Implement Core Search Logic
- **Query Matching**: Fuzzy search across slug, name, aliases, keywords
- **Ranking Algorithm**: Prioritize exact matches, then prefix matches, then fuzzy
- **Metadata Enrichment**: Include usage, examples, category in results
- **Performance**: Sub-100ms response time for typical queries

#### 1.3 Alias Resolution System
```php
// Example alias resolution logic
public function resolveAlias(string $input): ?string
{
    // Direct slug match
    if ($canonical = $this->directSlugLookup($input)) {
        return $canonical;
    }
    
    // Alias-to-canonical mapping
    if ($canonical = $this->aliasLookup($input)) {
        return $canonical;
    }
    
    return null; // No match found
}
```

### Phase 2: Controller Integration (3-4 hours)

#### 2.1 Update AutocompleteController
**File**: `app/Http/Controllers/AutocompleteController.php`

**Transform from**:
```php
$commands = CommandRegistry::getAllCommands(); // Static
```

**To**:
```php
$autocompleteService = app(AutocompleteService::class);
$commands = $autocompleteService->search($query, $limit);
```

#### 2.2 Enhanced API Response Format
```json
{
  "commands": [
    {
      "slug": "search",
      "name": "Search Command",
      "category": "Query",
      "summary": "Search through fragments and resources",
      "usage": "/search <query> [--type=fragment]",
      "examples": [
        "/search react components",
        "/search --type=fragment authentication"
      ],
      "aliases": ["s", "find"],
      "keywords": ["query", "lookup", "find"]
    }
  ],
  "meta": {
    "query": "se",
    "total": 1,
    "cache_hit": true
  }
}
```

#### 2.3 API Endpoint Updates
- **Maintain compatibility** with existing `/api/autocomplete/commands`
- **Add parameters**: `?query=`, `?limit=`, `?category=`
- **Response headers**: Cache control and performance metrics

### Phase 3: Caching Strategy (2-3 hours)

#### 3.1 Multi-Level Caching
```php
// Application-level cache (30 minutes)
$cacheKey = "autocomplete:commands:" . md5($query);
return Cache::remember($cacheKey, 1800, function() use ($query) {
    return $this->performDatabaseSearch($query);
});

// Full command index cache (4 hours, invalidated on pack changes)
$indexKey = "autocomplete:index";
return Cache::remember($indexKey, 14400, function() {
    return $this->buildSearchIndex();
});
```

#### 3.2 Cache Invalidation Strategy
- **Trigger**: `frag:command:cache` invalidation events
- **Scope**: Clear both query-specific and index caches
- **Performance**: Warm cache proactively after pack changes

#### 3.3 Cache Monitoring
- **Metrics**: Hit/miss ratios, query performance, cache size
- **Logging**: Cache rebuild events, slow queries
- **Alerting**: Cache invalidation failures

### Phase 4: Frontend Enhancement (2-3 hours)

#### 4.1 Client-Side Debouncing
**File**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`

```typescript
import { debounce } from 'lodash';

const debouncedFetch = debounce(async (query: string) => {
  if (query.length < 2) return [];
  
  const cached = getFromClientCache(query);
  if (cached) return cached;
  
  const response = await fetch(`/api/autocomplete/commands?query=${query}`);
  const data = await response.json();
  
  setClientCache(query, data.commands);
  return data.commands;
}, 300); // 300ms debounce
```

#### 4.2 Client-Side Caching
- **Strategy**: LRU cache with 5-minute TTL
- **Size Limit**: 50 cached queries maximum
- **Invalidation**: Clear on page reload or explicit cache bust

#### 4.3 Rich Metadata Display
```typescript
interface AutocompleteResult {
  slug: string;
  name: string;
  category: string;
  summary: string;
  usage?: string;
  examples?: string[];
  aliases?: string[];
}

// Enhanced suggestion rendering with metadata
const renderSuggestion = (command: AutocompleteResult) => (
  <div className="autocomplete-item">
    <div className="command-header">
      <span className="command-name">{command.name}</span>
      <span className="command-category">{command.category}</span>
    </div>
    <div className="command-summary">{command.summary}</div>
    {command.usage && (
      <div className="command-usage">Usage: {command.usage}</div>
    )}
  </div>
);
```

## Testing Strategy

### Unit Tests
**File**: `tests/Unit/AutocompleteServiceTest.php`

```php
class AutocompleteServiceTest extends TestCase
{
    public function test_search_returns_exact_matches_first()
    public function test_search_includes_alias_matches()
    public function test_search_respects_limit_parameter()
    public function test_alias_resolution_works_correctly()
    public function test_caching_improves_performance()
}
```

### Feature Tests
**File**: `tests/Feature/AutocompleteApiTest.php`

```php
class AutocompleteApiTest extends TestCase
{
    public function test_autocomplete_endpoint_returns_valid_json()
    public function test_query_parameter_filters_results()
    public function test_response_includes_rich_metadata()
    public function test_alias_queries_return_canonical_commands()
}
```

### Frontend Tests
**File**: `resources/js/__tests__/SlashCommand.test.tsx`

- Debouncing behavior verification
- Client-side cache functionality
- Metadata display rendering
- Keyboard navigation integration

## Performance Targets

### API Response Times
- **Cold cache**: < 200ms for typical queries
- **Warm cache**: < 50ms for cached queries
- **Complex queries**: < 500ms for multi-term searches

### Cache Performance
- **Hit ratio**: > 80% for steady-state usage
- **Index rebuild**: < 5 seconds for full registry
- **Memory usage**: < 10MB for cached autocomplete data

### Frontend Performance
- **Debounce delay**: 300ms (balance responsiveness vs API load)
- **Client cache**: 50 queries max, 5-minute TTL
- **Rendering**: < 16ms for suggestion list updates

## Risk Mitigation

### Database Performance
- **Risk**: Slow queries on large command registries
- **Mitigation**: Database indexing on searchable fields
- **Monitoring**: Query execution time logging

### Cache Coherence
- **Risk**: Stale autocomplete data after pack changes
- **Mitigation**: Event-driven cache invalidation
- **Testing**: Cache invalidation integration tests

### Frontend Stability
- **Risk**: Client cache memory leaks
- **Mitigation**: LRU eviction and TTL cleanup
- **Monitoring**: Client-side performance metrics

## Success Criteria

### Functional Requirements
- [ ] All commands discoverable via autocomplete within 5 seconds of registry update
- [ ] Alias resolution works for all registered aliases (e.g., `/s` → `search`)
- [ ] Rich metadata displays correctly in autocomplete suggestions
- [ ] Query debouncing reduces API calls by > 70%

### Performance Requirements
- [ ] API response time < 200ms (95th percentile)
- [ ] Cache hit ratio > 80% during steady-state usage
- [ ] Client-side debouncing delays requests by 300ms
- [ ] Memory usage stays under 10MB for autocomplete caches

### Quality Requirements
- [ ] Unit test coverage > 90% for AutocompleteService
- [ ] Feature test coverage for all API endpoints
- [ ] Frontend tests verify debouncing and caching behavior
- [ ] Integration tests confirm cache invalidation works

This plan provides a comprehensive roadmap for replacing static autocomplete with a dynamic, high-performance service that supports aliases and rich metadata.