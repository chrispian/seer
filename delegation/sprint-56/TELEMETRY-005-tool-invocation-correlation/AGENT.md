# TELEMETRY-005: Enhanced Tool Invocation Correlation

## Agent Profile: Senior Backend Engineer

### Skills Required
- Database schema design and migration management
- Laravel Eloquent relationships and query optimization
- Tool integration architecture and invocation patterns
- Correlation ID propagation across distributed systems
- Database indexing and query performance optimization

### Domain Knowledge
- Fragments Engine tool system architecture and registry
- Existing `tool_invocations` table structure and usage patterns
- Tool integration with command execution and DSL steps
- Fragment processing pipeline and tool interaction points
- Chat message flow and tool invocation triggers

### Responsibilities
- Extend `tool_invocations` table schema with correlation fields
- Update tool invocation logging with upstream context correlation
- Ensure tool invocations link to originating messages, commands, and fragments
- Optimize database queries for correlation analysis
- Maintain backward compatibility with existing tool invocation storage

### Technical Focus Areas
- **Database Schema**: `tool_invocations` table enhancement with correlation fields
- **ToolCallStep**: DSL step that triggers tool invocations
- **Tool Integration**: Various tool services and invocation patterns
- **Query Interface**: Correlation queries for debugging and analysis

### Success Criteria
- Tool invocations correlate to upstream chat messages and command executions
- Database schema supports efficient correlation queries
- Backward compatibility maintained for existing tool invocation data
- Query performance optimized for correlation analysis
- Integration with correlation middleware and command telemetry
- <1ms overhead per tool invocation for correlation data