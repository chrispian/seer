# Testing Guide

This guide covers the testing infrastructure for Seer, including setup, running tests, and best practices.

## Quick Start

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit      # Unit tests only
composer test:feature   # Feature tests only
composer test:integration # Integration tests only

# Run tests in parallel (faster)
composer test:parallel

# Run tests with coverage
composer test:coverage

# Run fast tests (exclude slow groups)
composer test:fast
```

## Test Organization

### Test Suites

Tests are organized into three main categories:

- **Unit Tests** (`tests/Unit/`): Fast, isolated tests for individual classes and methods
- **Feature Tests** (`tests/Feature/`): Tests for application features with database interactions
- **Integration Tests** (`tests/Integration/`): End-to-end tests with external services

### Test Groups

Tests are tagged with groups for selective execution:

- `@group integration` - Integration tests with external dependencies
- `@group pipeline` - Fragment processing pipeline tests
- `@group embeddings` - AI embeddings-related tests
- `@group routing` - Vault routing functionality tests
- `@group slow` - Tests that take longer to execute

## Environment Configuration

### Test Database

Tests use SQLite in-memory database by default for speed:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Performance Optimizations

The following optimizations are enabled for testing:

- Disabled embeddings: `FRAGMENTS_EMBEDDINGS_ENABLED=false`
- Reduced bcrypt rounds: `BCRYPT_ROUNDS=4`
- Array cache driver: `CACHE_STORE=array`
- Null log channel: `LOG_CHANNEL=null`
- Debug disabled: `APP_DEBUG=false`

### AI Provider Mocking

For tests involving AI providers, use the helper functions:

```php
// Mock OpenAI provider
mockAIProvider('openai');

// Mock Ollama provider
mockAIProvider('ollama');
```

## Factory Usage

### Fragment Factory

Enhanced with multiple states for testing different scenarios:

```php
// Basic fragment
Fragment::factory()->create();

// Fragment with embeddings (AI-enabled scenario)
Fragment::factory()
    ->withEmbeddings()
    ->withAIMetadata('openai', 'gpt-4o-mini')
    ->create();

// Fragment without embeddings (AI-disabled scenario)
Fragment::factory()->withoutEmbeddings()->create();

// Complex fragment with entities
Fragment::factory()
    ->withComplexContent()
    ->withEntities()
    ->create();

// Specific types
Fragment::factory()->todo()->create();
Fragment::factory()->meeting()->create();

// With vault assignment
Fragment::factory()->withVault('work', $projectId)->create();
```

### ChatSession Factory

For testing chat/conversation functionality:

```php
// Basic chat session
ChatSession::factory()->create();

// Chat with conversation history
ChatSession::factory()
    ->withConversation()
    ->create();

// Pinned chat session
ChatSession::factory()
    ->pinned()
    ->withCustomName('project-planning')
    ->create();

// Provider-specific sessions
ChatSession::factory()->openai()->create();
ChatSession::factory()->ollama()->create();
```

## Test Data Seeding

### TestDataSeeder

For comprehensive test scenarios, use the TestDataSeeder:

```php
// In your test
$this->seed(\Database\Seeders\TestDataSeeder::class);
```

This creates:
- Test users (test@example.com, admin@example.com)
- Vaults: work, personal, clients
- Projects for each vault
- Fragment types
- Routing rules
- Sample fragments with different states
- Chat sessions with various configurations

### Helper Functions

Global test helper functions are available:

```php
// Create and authenticate test user
$user = actingAsTestUser();

// Create test user without authentication
$user = createTestUser();

// Create test fragment with custom attributes
$fragment = createTestFragment(['type' => 'meeting']);

// Mock AI provider configuration
mockAIProvider('openai');
```

## Running Tests

### Local Development

```bash
# Standard test run
vendor/bin/pest

# Run specific test file
vendor/bin/pest tests/Feature/FragmentTest.php

# Run specific test method
vendor/bin/pest --filter="test_fragment_creation"

# Run tests with specific group
vendor/bin/pest --group=pipeline

# Exclude slow tests
vendor/bin/pest --exclude-group=slow

# Run with coverage
vendor/bin/pest --coverage --min=80
```

### Parallel Testing

Parallel testing is configured for improved performance:

```bash
# Using Composer script (recommended)
composer test:parallel

# Using artisan directly
php artisan test --parallel

# Specify number of processes
php artisan test --parallel --processes=4
```

### CI/GitHub Actions

For CI environments, use:

```bash
composer test:ci
```

This runs tests with optimizations for CI environments.

## Performance Benchmarks

### Baseline Performance (Before Improvements)
- Total execution time: ~8.4s
- Failed tests: 42/118
- Database issues: RefreshDatabase not enabled
- No parallel processing

### Target Performance (After Improvements)
- Total execution time: ~4-5s (40-50% improvement)
- All tests passing
- Parallel execution enabled
- Optimized database configuration

## Writing Tests

### Best Practices

1. **Use appropriate test types**:
   - Unit tests for pure logic
   - Feature tests for HTTP/database interactions
   - Integration tests for full workflows

2. **Use factories and seeders**:
   ```php
   // Good: Use factories
   $fragment = Fragment::factory()->withEmbeddings()->create();

   // Avoid: Manual attribute setting
   $fragment = new Fragment(['embedding' => [...], ...]);
   ```

3. **Group related tests**:
   ```php
   it('processes embeddings correctly', function () {
       // test code
   })->group('embeddings', 'ai');
   ```

4. **Use helper functions**:
   ```php
   // Good: Use helper
   $user = actingAsTestUser();

   // Avoid: Manual setup
   $user = User::factory()->create();
   $this->actingAs($user);
   ```

### Test Structure

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup code for all tests in this file
    mockAIProvider('openai');
});

it('performs expected behavior', function () {
    // Arrange
    $fragment = Fragment::factory()->create();

    // Act
    $result = $fragment->process();

    // Assert
    expect($result)->toBeValidFragment();
})->group('feature');
```

## Troubleshooting

### Common Issues

1. **Database Errors**: Ensure RefreshDatabase trait is used
2. **Postgres-specific Features**: Some tests may fail on SQLite, consider database-specific testing
3. **Parallel Test Failures**: Some tests may not be parallel-safe, use appropriate grouping
4. **Memory Issues**: Large test suites may require increased PHP memory limit

### Debug Commands

```bash
# Run single test with verbose output
vendor/bin/pest --filter="test_name" -v

# Run with debug output
vendor/bin/pest --debug

# Check test configuration
vendor/bin/pest --list-tests
```

## Contributing

When adding new tests:

1. Follow the established directory structure
2. Use appropriate factories and helpers
3. Add proper test groups
4. Ensure tests are fast and reliable
5. Update this documentation if adding new patterns

For questions about testing, check existing tests for patterns or consult the team.