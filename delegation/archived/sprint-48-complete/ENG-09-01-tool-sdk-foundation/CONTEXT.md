# ENG-09-01: Tool SDK & Registry Foundation - Technical Context

## Implementation Context

### Current Architecture Integration
The Tool SDK must integrate seamlessly with the Fragments Engine's existing patterns:
- **Service Layer**: Follow established `app/Services/` patterns with dependency injection
- **Provider System**: Leverage Laravel's service provider architecture for tool registration
- **Configuration**: Use `config/` files with environment-based overrides
- **Middleware**: Implement as Laravel middleware for request/response telemetry

### Existing Code Patterns to Follow
- **AI Service Abstraction**: Similar to `app/Services/AI/` provider pattern
- **Command System**: JSON contract validation like YAML DSL schema validation
- **Fragment System**: Model patterns with JSON schema validation
- **Queue System**: Background processing patterns for tool execution

## Technical Requirements

### Tool Contract Interface
```php
interface ToolContract
{
    public function getName(): string;
    public function getVersion(): string;
    public function getSchema(): array;
    public function getScopes(): array;
    public function execute(array $input): array;
    public function validate(array $input): bool;
}
```

### Registry Architecture
- **Dynamic Discovery**: Scan `app/Tools/` directory for contract implementations
- **Schema Validation**: JSON schema validation for tool inputs/outputs
- **Caching**: Laravel cache integration for performance optimization
- **Versioning**: Support multiple versions of the same tool

### Telemetry Integration
- **Middleware**: Capture tool execution metrics automatically
- **Metrics**: Duration, success/failure, input/output sizes, error rates
- **Storage**: Integrate with existing Laravel logging and metrics systems
- **Privacy**: Hash sensitive inputs/outputs for audit trails

## Dependencies and Integration Points

### Laravel Framework Integration
- **Service Container**: Tool binding and resolution
- **Configuration**: `config/tools.php` for scopes, quotas, and settings
- **Logging**: Laravel log channels for tool execution audit
- **Cache**: Redis/file cache for tool registry and schema caching

### Fragments Engine Integration
- **Models**: Potential future `Tool` model for persistence
- **Jobs**: Background tool execution through existing queue system
- **API**: Internal endpoints following existing API patterns
- **Security**: Authentication and authorization patterns

### Future Tool Dependencies
This foundation must support:
- **DbQueryTool**: Database query tool with saved queries
- **ExportTool**: Export generation with artifact management
- **MemoryTool**: Agent memory storage and retrieval
- **ShellTool**: Safe shell command execution
- **FileSystemTool**: Controlled file operations

## Performance Requirements

### Execution Performance
- Tool registry lookup: < 5ms
- Schema validation: < 10ms per tool
- Telemetry overhead: < 2ms per execution
- Memory usage: < 50MB for full registry

### Caching Strategy
- **Registry Cache**: Tool definitions cached for 1 hour
- **Schema Cache**: JSON schemas cached indefinitely with version keys
- **Telemetry Buffer**: Batch telemetry writes for performance

## Security Considerations

### Scope and Permission System
- **Tool Scopes**: read, write, admin, system levels
- **User Permissions**: Integration with existing user authorization
- **Quota Limits**: Per-user, per-tool execution limits
- **Audit Trail**: Complete execution history with input/output hashing

### Input Validation
- **JSON Schema**: Strict input validation against tool contracts
- **Sanitization**: Input cleaning and normalization
- **Rate Limiting**: Prevent tool abuse and resource exhaustion

## Implementation Files to Create

### Core Framework
- `app/Contracts/ToolContract.php` - Main tool interface
- `app/Support/ToolRegistry.php` - Tool discovery and registration
- `app/Providers/ToolServiceProvider.php` - Laravel service integration
- `app/Http/Middleware/ToolTelemetry.php` - Execution telemetry middleware

### Configuration and Schema
- `config/tools.php` - Tool configuration, scopes, and quotas
- `resources/tools/contracts/` - JSON schema directory for tool contracts

### Testing Framework
- `tests/Unit/ToolRegistryTest.php` - Registry functionality tests
- `tests/Feature/ToolExecutionTest.php` - End-to-end tool execution tests

## Quality Standards

### Code Quality
- PSR-12 compliant with comprehensive type declarations
- 100% interface coverage with phpDoc documentation
- Dependency injection for all tool implementations
- Exception handling with clear error messages

### Testing Requirements
- Unit tests for all registry functionality
- Integration tests for tool execution pipeline
- Performance tests for registry lookup and caching
- Security tests for scope and permission validation

### Documentation Standards
- Complete API documentation for all interfaces
- Tool development guide for future implementations
- Integration examples for common patterns
- Performance optimization recommendations