# DSL-UX-005: Observability & Testing - Implementation Plan

## Overview
Establish comprehensive testing coverage and observability for the DSL command system to ensure reliability, performance monitoring, and maintainability.

**Dependencies**: DSL-UX-001, DSL-UX-002, DSL-UX-003, DSL-UX-004 (testing their implementations)  
**Estimated Time**: 6-8 hours  
**Priority**: HIGH (quality assurance foundation)

## Implementation Phases

### Phase 1: Core Testing Infrastructure (2-3 hours)

#### 1.1 Unit Test Foundation (1.5 hours)
**AutocompleteService Tests**:
```php
// tests/Unit/AutocompleteServiceTest.php
class AutocompleteServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private AutocompleteService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutocompleteService::class);
        
        // Create test data
        CommandRegistry::factory()
            ->count(10)
            ->create([
                'category' => 'Database',
                'aliases' => json_encode(['s', 'find']),
                'keywords' => json_encode(['search', 'query', 'lookup'])
            ]);
    }
    
    public function test_search_returns_exact_matches_first(): void
    {
        $results = $this->service->search('search');
        
        $this->assertNotEmpty($results);
        $this->assertEquals('search', $results->first()['slug']);
    }
    
    public function test_search_includes_alias_matches(): void
    {
        $results = $this->service->search('s');
        
        $this->assertContains('search', $results->pluck('slug'));
    }
    
    public function test_alias_resolution_works(): void
    {
        $canonical = $this->service->resolveAlias('s');
        
        $this->assertEquals('search', $canonical);
    }
    
    public function test_caching_improves_performance(): void
    {
        // First call (no cache)
        $start = microtime(true);
        $this->service->search('test');
        $firstCallTime = microtime(true) - $start;
        
        // Second call (with cache)
        $start = microtime(true);
        $this->service->search('test');
        $secondCallTime = microtime(true) - $start;
        
        $this->assertLessThan($firstCallTime, $secondCallTime);
    }
}
```

**HelpService Tests**:
```php
// tests/Unit/HelpServiceTest.php
class HelpServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private HelpService $helpService;
    
    public function test_generates_full_help_structure(): void
    {
        $helpData = $this->helpService->generateHelp();
        
        $this->assertArrayHasKey('overview', $helpData);
        $this->assertArrayHasKey('categories', $helpData);
        $this->assertArrayHasKey('meta', $helpData);
    }
    
    public function test_filters_help_by_category(): void
    {
        $helpData = $this->helpService->generateHelp(category: 'Database');
        
        $commands = collect($helpData['categories'])->flatten(1);
        $this->assertTrue($commands->every(fn($cmd) => $cmd['category'] === 'Database'));
    }
    
    public function test_searches_help_content(): void
    {
        $results = $this->helpService->searchHelp('database');
        
        $this->assertNotEmpty($results);
        $this->assertTrue($results->contains(fn($cmd) => 
            str_contains(strtolower($cmd['summary']), 'database')
        ));
    }
    
    public function test_caches_help_content(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('help:full:v1', 14400, Mockery::any())
            ->andReturn(['cached' => true]);
            
        $result = $this->helpService->generateHelp();
        $this->assertEquals(['cached' => true], $result);
    }
}
```

#### 1.2 API Integration Tests (1 hour)
**Autocomplete API Tests**:
```php
// tests/Feature/AutocompleteApiTest.php
class AutocompleteApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_autocomplete_endpoint_returns_valid_json(): void
    {
        CommandRegistry::factory()->create(['slug' => 'search']);
        
        $response = $this->getJson('/api/autocomplete/commands?query=search');
        
        $response->assertOk()
            ->assertJsonStructure([
                'commands' => [
                    '*' => ['slug', 'name', 'category', 'summary', 'usage']
                ],
                'meta' => ['query', 'total', 'cache_hit']
            ]);
    }
    
    public function test_query_parameter_filters_results(): void
    {
        CommandRegistry::factory()->create(['slug' => 'search']);
        CommandRegistry::factory()->create(['slug' => 'job']);
        
        $response = $this->getJson('/api/autocomplete/commands?query=sea');
        
        $commands = $response->json('commands');
        $this->assertCount(1, $commands);
        $this->assertEquals('search', $commands[0]['slug']);
    }
    
    public function test_response_includes_performance_metadata(): void
    {
        $response = $this->getJson('/api/autocomplete/commands?query=test');
        
        $meta = $response->json('meta');
        $this->assertArrayHasKey('query_time_ms', $meta);
        $this->assertArrayHasKey('cache_hit', $meta);
        $this->assertIsNumeric($meta['query_time_ms']);
    }
}
```

**Help API Tests**:
```php
// tests/Feature/HelpApiTest.php
class HelpApiTest extends TestCase
{
    public function test_help_endpoint_returns_complete_structure(): void
    {
        $response = $this->getJson('/api/commands/help');
        
        $response->assertOk()
            ->assertJsonStructure([
                'help' => [
                    'overview' => ['total_commands', 'categories'],
                    'categories' => [
                        '*' => ['name', 'commands' => [
                            '*' => ['slug', 'name', 'summary', 'usage', 'examples']
                        ]]
                    ]
                ],
                'meta' => ['generated_at', 'cache_key']
            ]);
    }
    
    public function test_category_filtering(): void
    {
        $response = $this->getJson('/api/commands/help?category=Database');
        
        $categories = $response->json('help.categories');
        $this->assertCount(1, $categories);
        $this->assertEquals('Database', $categories[0]['name']);
    }
}
```

#### 1.3 Performance Testing Framework (30 minutes)
**Performance Test Base Class**:
```php
// tests/Performance/PerformanceTestCase.php
abstract class PerformanceTestCase extends TestCase
{
    protected function measureExecutionTime(callable $operation): float
    {
        $start = microtime(true);
        $operation();
        return (microtime(true) - $start) * 1000; // Convert to milliseconds
    }
    
    protected function assertExecutionTimeUnder(float $maxMs, callable $operation): void
    {
        $executionTime = $this->measureExecutionTime($operation);
        $this->assertLessThan($maxMs, $executionTime, 
            "Operation took {$executionTime}ms, expected under {$maxMs}ms"
        );
    }
    
    protected function runConcurrentRequests(string $endpoint, int $count = 10): Collection
    {
        return collect(range(1, $count))->map(function() use ($endpoint) {
            return Http::async()->get($endpoint);
        })->map->wait();
    }
}
```

### Phase 2: Frontend Testing Implementation (2 hours)

#### 2.1 Component Unit Tests (1 hour)
**SlashCommand Component Tests**:
```typescript
// resources/js/__tests__/SlashCommand.test.tsx
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { SlashCommandList } from '../islands/chat/tiptap/extensions/SlashCommand'

const mockCommands = [
  {
    slug: 'search',
    name: 'Search Command',
    category: 'Query',
    summary: 'Search through fragments',
    usage: '/search <query>',
    examples: ['/search react'],
    aliases: ['s']
  }
]

describe('SlashCommandList', () => {
  test('renders suggestions correctly', () => {
    render(<SlashCommandList items={mockCommands} isVisible={true} />)
    
    expect(screen.getByText('Search Command')).toBeInTheDocument()
    expect(screen.getByText('Search through fragments')).toBeInTheDocument()
  })
  
  test('handles keyboard navigation', () => {
    const onSelect = jest.fn()
    render(
      <SlashCommandList 
        items={mockCommands} 
        isVisible={true}
        onSelect={onSelect}
      />
    )
    
    // Navigate and select
    fireEvent.keyDown(document, { key: 'ArrowDown' })
    fireEvent.keyDown(document, { key: 'Enter' })
    
    expect(onSelect).toHaveBeenCalledWith(mockCommands[0])
  })
  
  test('prevents default on navigation keys', () => {
    render(<SlashCommandList items={mockCommands} isVisible={true} />)
    
    const event = new KeyboardEvent('keydown', { key: 'ArrowDown' })
    const preventDefaultSpy = jest.spyOn(event, 'preventDefault')
    
    document.dispatchEvent(event)
    
    expect(preventDefaultSpy).toHaveBeenCalled()
  })
})
```

**Autocomplete Hook Tests**:
```typescript
// resources/js/__tests__/useAutocomplete.test.tsx
import { renderHook, act } from '@testing-library/react'
import { useAutocomplete } from '../hooks/useAutocomplete'

// Mock fetch
global.fetch = jest.fn()

describe('useAutocomplete', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })
  
  test('debounces API calls', async () => {
    const { result } = renderHook(() => useAutocomplete())
    
    act(() => {
      result.current.search('s')
      result.current.search('se')
      result.current.search('sea')
    })
    
    // Wait for debounce
    await waitFor(() => {
      expect(fetch).toHaveBeenCalledTimes(1)
    })
  })
  
  test('caches results', async () => {
    const mockResponse = { commands: mockCommands }
    ;(fetch as jest.Mock).mockResolvedValue({
      json: () => Promise.resolve(mockResponse)
    })
    
    const { result } = renderHook(() => useAutocomplete())
    
    // First call
    await act(async () => {
      await result.current.search('search')
    })
    
    // Second call (should use cache)
    await act(async () => {
      await result.current.search('search')
    })
    
    expect(fetch).toHaveBeenCalledTimes(1)
  })
})
```

#### 2.2 E2E Testing Setup (1 hour)
**Cypress Integration Tests**:
```typescript
// cypress/integration/slash-command-flow.spec.ts
describe('Slash Command Flow', () => {
  beforeEach(() => {
    cy.visit('/chat')
    cy.intercept('GET', '/api/autocomplete/commands*', { 
      fixture: 'autocomplete-commands.json' 
    }).as('getCommands')
  })
  
  it('completes full command discovery flow', () => {
    // Type slash to trigger autocomplete
    cy.get('[data-testid="chat-input"]').type('/')
    
    // Wait for suggestions
    cy.wait('@getCommands')
    cy.get('[data-testid="suggestion-list"]').should('be.visible')
    
    // Navigate with keyboard
    cy.get('body').type('{downarrow}')
    cy.get('[data-testid="suggestion-item"]').eq(1).should('have.class', 'selected')
    
    // Select command
    cy.get('body').type('{enter}')
    cy.get('[data-testid="chat-input"]').should('contain.value', '/search')
  })
  
  it('handles help command flow', () => {
    cy.get('[data-testid="chat-input"]').type('/help{enter}')
    
    cy.get('[data-testid="help-modal"]').should('be.visible')
    cy.get('[data-testid="help-categories"]').should('contain', 'Database')
    cy.get('[data-testid="help-categories"]').should('contain', 'AI')
  })
  
  it('measures performance', () => {
    const start = performance.now()
    
    cy.get('[data-testid="chat-input"]').type('/sea')
    cy.wait('@getCommands')
    
    cy.then(() => {
      const duration = performance.now() - start
      expect(duration).to.be.lessThan(500) // 500ms max
    })
  })
})
```

### Phase 3: Observability Implementation (2-3 hours)

#### 3.1 Metrics Collection (1.5 hours)
**Performance Metrics Service**:
```php
// app/Services/MetricsService.php
class MetricsService
{
    public function recordCommandRegistryRebuild(array $metrics): void
    {
        Log::info('Command registry rebuild completed', [
            'duration_ms' => $metrics['duration_ms'],
            'command_count' => $metrics['command_count'],
            'pack_count' => $metrics['pack_count'],
            'conflicts_resolved' => $metrics['conflicts_resolved'] ?? 0
        ]);
        
        // Send to metrics system (StatsD, Prometheus, etc.)
        $this->sendMetric('command_registry.rebuild.duration', $metrics['duration_ms']);
        $this->sendMetric('command_registry.rebuild.commands', $metrics['command_count']);
    }
    
    public function recordAutocompleteQuery(array $metrics): void
    {
        Log::info('Autocomplete query processed', [
            'query' => $metrics['query'],
            'result_count' => $metrics['result_count'],
            'response_time_ms' => $metrics['response_time_ms'],
            'cache_hit' => $metrics['cache_hit']
        ]);
        
        $this->sendMetric('autocomplete.response_time', $metrics['response_time_ms']);
        $this->sendMetric('autocomplete.cache_hit', $metrics['cache_hit'] ? 1 : 0);
    }
    
    public function recordHelpGeneration(array $metrics): void
    {
        Log::info('Help content generated', [
            'type' => $metrics['type'], // 'full', 'category', 'search'
            'generation_time_ms' => $metrics['generation_time_ms'],
            'cache_hit' => $metrics['cache_hit'],
            'content_size_kb' => $metrics['content_size_kb']
        ]);
    }
    
    private function sendMetric(string $name, float $value): void
    {
        // Integration with metrics backend
        // This could be StatsD, Prometheus, CloudWatch, etc.
    }
}
```

**Middleware for API Metrics**:
```php
// app/Http/Middleware/ApiMetricsMiddleware.php
class ApiMetricsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000;
        
        if ($this->shouldTrackEndpoint($request)) {
            app(MetricsService::class)->recordApiRequest([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->status(),
                'response_time_ms' => $duration
            ]);
        }
        
        return $response;
    }
    
    private function shouldTrackEndpoint(Request $request): bool
    {
        return str_starts_with($request->path(), 'api/autocomplete') ||
               str_starts_with($request->path(), 'api/commands/help');
    }
}
```

#### 3.2 Health Check System (1 hour)
**Health Check Controller**:
```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function commands(): JsonResponse
    {
        $checks = [
            'registry' => $this->checkCommandRegistry(),
            'autocomplete' => $this->checkAutocomplete(),
            'help_system' => $this->checkHelpSystem(),
            'cache' => $this->checkCacheSystem()
        ];
        
        $overallStatus = collect($checks)->every(fn($check) => $check['status'] === 'healthy')
            ? 'healthy' 
            : 'unhealthy';
        
        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'components' => $checks
        ]);
    }
    
    private function checkCommandRegistry(): array
    {
        try {
            $commandCount = CommandRegistry::count();
            $lastUpdate = Cache::get('command_registry:last_update');
            
            return [
                'status' => 'healthy',
                'command_count' => $commandCount,
                'last_update' => $lastUpdate,
                'staleness_hours' => $lastUpdate ? now()->diffInHours($lastUpdate) : null
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkAutocomplete(): array
    {
        try {
            $start = microtime(true);
            $autocompleteService = app(AutocompleteService::class);
            $results = $autocompleteService->search('test');
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => $responseTime < 500 ? 'healthy' : 'degraded',
                'response_time_ms' => round($responseTime, 2),
                'result_count' => $results->count()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkHelpSystem(): array
    {
        try {
            $cacheSize = $this->calculateHelpCacheSize();
            
            return [
                'status' => 'healthy',
                'cache_size_mb' => round($cacheSize / 1024 / 1024, 2),
                'cached_items' => $this->countHelpCacheItems()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}
```

#### 3.3 Error Tracking Integration (30 minutes)
**Error Context Enhancement**:
```php
// app/Exceptions/Handler.php additions
public function report(Throwable $exception): void
{
    if ($this->isCommandSystemError($exception)) {
        Log::error('Command system error', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'context' => $this->getCommandSystemContext()
        ]);
    }
    
    parent::report($exception);
}

private function getCommandSystemContext(): array
{
    return [
        'registry_size' => CommandRegistry::count(),
        'cache_status' => Cache::has('command_registry:cache'),
        'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
        'request_id' => request()->header('X-Request-ID')
    ];
}
```

### Phase 4: Monitoring Dashboard (30 minutes)

#### 4.1 Basic Metrics Endpoint
**Metrics API for Dashboard**:
```php
// app/Http/Controllers/MetricsController.php
class MetricsController extends Controller
{
    public function commandSystem(): JsonResponse
    {
        return response()->json([
            'performance' => [
                'autocomplete_avg_response_ms' => $this->getAverageAutocompleteTime(),
                'help_generation_avg_ms' => $this->getAverageHelpGenerationTime(),
                'cache_hit_ratio' => $this->getCacheHitRatio()
            ],
            'usage' => [
                'total_commands' => CommandRegistry::count(),
                'queries_last_hour' => $this->getQueriesLastHour(),
                'popular_commands' => $this->getPopularCommands()
            ],
            'health' => [
                'error_rate_percent' => $this->getErrorRatePercent(),
                'uptime_percent' => $this->getUptimePercent()
            ]
        ]);
    }
}
```

## Testing Execution Strategy

### Test Execution Order
1. **Unit Tests**: Foundation testing for all services
2. **Integration Tests**: API and database interaction testing
3. **Performance Tests**: Baseline establishment and regression detection
4. **E2E Tests**: Complete user journey validation

### Continuous Integration Setup
**GitHub Actions/CI Pipeline**:
```yaml
# .github/workflows/test.yml (partial)
- name: Run Unit Tests
  run: php artisan test --parallel --coverage

- name: Run Frontend Tests  
  run: npm run test:coverage

- name: Run E2E Tests
  run: npm run cypress:run

- name: Performance Testing
  run: php artisan test --group=performance
```

### Test Data Management
**Consistent Test Environment**:
- Database seeding for predictable test data
- Cache clearing between test suites
- Mock external dependencies
- Isolated test environments

## Success Criteria

### Coverage Targets
- **Unit Test Coverage**: >90% for service classes
- **Integration Test Coverage**: >80% for API endpoints
- **E2E Coverage**: 100% of critical user paths

### Performance Targets
- **Test Execution Time**: Full test suite under 5 minutes
- **Monitoring Overhead**: <5% performance impact
- **Alert Response Time**: Critical alerts within 2 minutes

### Quality Metrics
- **Flaky Test Rate**: <5% of test runs
- **False Positive Alerts**: <10% of alerts
- **Documentation Coverage**: All observability features documented

This plan establishes comprehensive testing and observability that ensures the DSL command system remains reliable and performant.