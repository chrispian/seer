# ENG-10-06: Testing & Validation

## Agent Profile
**Type**: QA Engineer / Test Automation Specialist
**Expertise**: Pest testing, integration testing, performance testing, E2E testing
**Focus**: Test coverage, quality assurance, performance validation

## Mission
Comprehensively test the tool execution pipeline, ensuring all components work correctly, handle errors gracefully, and meet performance requirements.

## Current Context
- Tool execution pipeline implemented (ENG-10-01 through ENG-10-05)
- Existing Pest test framework in use
- Need comprehensive test coverage
- Performance benchmarks required

## Skills Required
- Pest/PHPUnit testing
- Integration testing strategies
- Performance testing tools
- Mock/stub creation
- E2E testing approaches

## Success Metrics
- 100% of tool-crate tools tested
- All error scenarios covered
- Performance benchmarks met
- Zero regression in existing features
- Clear test documentation
- CI/CD ready test suite

## Deliverables

### 1. Unit Tests
```php
// tests/Unit/Services/Tools/ToolCommandDetectorTest.php
class ToolCommandDetectorTest extends TestCase
{
    public function test_detects_mcp_tool_commands()
    public function test_extracts_tool_parameters()
    public function test_distinguishes_command_types()
}

// tests/Unit/Services/Tools/MCPToolExecutorTest.php
class MCPToolExecutorTest extends TestCase
{
    public function test_executes_tool_successfully()
    public function test_handles_invalid_parameters()
    public function test_handles_tool_errors()
}
```

### 2. Integration Tests
```php
// tests/Feature/ToolExecutionTest.php
class ToolExecutionTest extends TestCase
{
    public function test_help_index_tool()
    public function test_file_read_tool()
    public function test_json_query_tool()
    public function test_text_search_tool()
    public function test_text_replace_tool()
}
```

### 3. E2E Tests
```php
// tests/Feature/ChatToolIntegrationTest.php
class ChatToolIntegrationTest extends TestCase
{
    public function test_tool_execution_from_chat()
    public function test_tool_output_streaming()
    public function test_fragment_creation_for_tools()
    public function test_error_display_in_chat()
}
```

### 4. Performance Tests
```php
// tests/Performance/ToolExecutionBenchmark.php
class ToolExecutionBenchmark
{
    public function benchmark_tool_detection()
    public function benchmark_tool_execution()
    public function benchmark_streaming_latency()
}
```

## Test Implementation

### Unit Test Examples
```php
it('detects MCP tool commands', function () {
    $detector = new ToolCommandDetector();
    
    expect($detector->isToolCommand('@help.index'))->toBeTrue();
    expect($detector->isToolCommand('/command'))->toBeFalse();
    expect($detector->isToolCommand('regular message'))->toBeFalse();
});

it('extracts tool parameters correctly', function () {
    $detector = new ToolCommandDetector();
    $toolCall = $detector->extractToolCall('@file.read path:"/etc/hosts" lines:10');
    
    expect($toolCall->tool)->toBe('file.read');
    expect($toolCall->parameters)->toMatchArray([
        'path' => '/etc/hosts',
        'lines' => 10
    ]);
});
```

### Integration Test Examples
```php
it('executes help.index tool successfully', function () {
    $executor = app(MCPToolExecutor::class);
    $result = $executor->execute('help.index', []);
    
    expect($result->success)->toBeTrue();
    expect($result->output)->toContain('Available tools');
    expect($result->executionTime)->toBeLessThan(100);
});

it('handles file.read with invalid path', function () {
    $executor = app(MCPToolExecutor::class);
    $result = $executor->execute('file.read', ['path' => '/nonexistent/file']);
    
    expect($result->success)->toBeFalse();
    expect($result->error)->toContain('File not found');
});
```

### E2E Test Examples
```php
it('executes tool from chat interface', function () {
    $response = $this->postJson('/api/chat/send', [
        'content' => '@help.index',
        'session_id' => 1
    ]);
    
    $response->assertOk();
    $response->assertJson([
        'type' => 'tool_execution',
        'stream_url' => expect.stringContaining('/stream-tool/')
    ]);
    
    // Test streaming endpoint
    $streamUrl = $response->json('stream_url');
    $streamResponse = $this->get($streamUrl);
    
    $streamResponse->assertOk();
    $streamResponse->assertHeader('Content-Type', 'text/event-stream');
});
```

### Performance Benchmarks
```php
it('detects commands within performance threshold', function () {
    $detector = new ToolCommandDetector();
    $message = '@file.read path:"/etc/hosts"';
    
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $detector->detectCommandType($message);
    }
    $duration = (microtime(true) - $start) * 1000;
    
    expect($duration / 1000)->toBeLessThan(10); // <10ms average
});
```

## Test Scenarios

### Happy Path Tests
1. Execute each tool-crate tool
2. Verify correct output format
3. Check fragment creation
4. Validate streaming works
5. Confirm UI updates

### Error Scenarios
1. Invalid tool name
2. Missing required parameters
3. Invalid parameter types
4. Tool execution failure
5. Network/connection errors
6. Timeout scenarios
7. Permission denied

### Edge Cases
1. Very large output
2. Binary output
3. Unicode/emoji in output
4. Concurrent tool executions
5. Rapid successive commands
6. Mixed tool/regular messages

### Performance Scenarios
1. Simple tool execution
2. Long-running tools
3. High-output tools
4. Multiple concurrent users
5. Queue backup scenarios

## Test Data & Fixtures

### Mock MCP Server
```php
class MockMCPServer
{
    public function callTool($tool, $params)
    {
        return match($tool) {
            'help.index' => $this->mockHelpIndex(),
            'file.read' => $this->mockFileRead($params),
            default => throw new ToolNotFoundException($tool)
        };
    }
}
```

### Test Fixtures
```php
// tests/fixtures/tool-outputs.php
return [
    'help_index' => file_get_contents(__DIR__ . '/outputs/help_index.txt'),
    'json_output' => json_decode(file_get_contents(__DIR__ . '/outputs/sample.json')),
    'code_output' => file_get_contents(__DIR__ . '/outputs/sample_code.php'),
];
```

## CI/CD Integration
```yaml
# .github/workflows/tests.yml
- name: Run Tool Tests
  run: |
    php artisan test --testsuite=Tools
    php artisan test:performance --group=tools
```

## Documentation
- Test coverage report
- Performance benchmark results
- Known limitations
- Test maintenance guide

## Dependencies
- All previous ENG-10-* tasks
- Pest testing framework
- Mock/stub utilities
- Performance testing tools

## Time Estimate
2-3 hours total:
- 45 min: Unit test implementation
- 45 min: Integration tests
- 30 min: E2E tests
- 30 min: Performance benchmarks
- 30 min: Documentation and cleanup