# TELEMETRY-005: Enhanced Tool Invocation Correlation - Context

## Current State Analysis

### Existing Tool Invocations Infrastructure
**Database Table**: `tool_invocations` (migration: `2025_10_03_224052_create_tool_invocations_table.php`)

**Current Schema**:
```sql
- id (uuid, primary)
- user_id (bigint, nullable) 
- tool_slug (string)
- command_slug (string, nullable)
- fragment_id (bigint, nullable)
- request (json, nullable)
- response (json, nullable)
- status (string, default 'ok')
- duration_ms (float, nullable)
- created_at (timestampTz)
```

**Current Indexes**:
- `(tool_slug, created_at)`
- `(user_id, created_at)`
- `(status, created_at)`

### Tool Invocation Points
**DSL Step**: `app/Services/Commands/DSL/Steps/ToolCallStep.php`
- Inserts tool invocations with basic context (line 85)
- Has `command_slug` and `fragment_id` but missing correlation fields
- No upstream message or execution correlation

**Current Logging Pattern**:
```php
DB::table('tool_invocations')->insert([
    'id' => (string) Str::uuid(),
    'user_id' => auth()->id(),
    'tool_slug' => $toolSlug,
    'command_slug' => $commandSlug ?? null,
    'fragment_id' => $fragmentId ?? null,
    'request' => json_encode($request),
    'response' => json_encode($response),
    'status' => $success ? 'ok' : 'error',
    'duration_ms' => $duration,
    'created_at' => now(),
]);
```

### Missing Correlation Context
**Chat → Tool Gap**:
- Chat messages trigger commands → commands trigger tools
- No way to trace from UI chat message to tool execution
- Missing `message_id` and `conversation_id` correlation

**Command → Tool Gap**:
- Commands execute DSL steps → DSL steps invoke tools
- Missing `command_execution_id` from TELEMETRY-004
- Missing `correlation_id` from TELEMETRY-001

**Fragment → Tool Gap**:
- Fragment processing triggers tools
- Missing processing job correlation
- No way to link tool invocations to fragment enrichment pipeline

## Target Enhanced Schema

### New Correlation Fields
```sql
ALTER TABLE tool_invocations ADD COLUMN:
- message_id (string, nullable) -- Chat message that triggered this tool
- conversation_id (string, nullable) -- Chat conversation context
- command_execution_id (string, nullable) -- Command execution from TELEMETRY-004
- correlation_id (string, nullable) -- Request correlation from TELEMETRY-001
- processing_job_id (string, nullable) -- Fragment processing job correlation
```

### New Indexes for Correlation Queries
```sql
- (correlation_id, created_at) -- Trace all activity in single request
- (message_id, created_at) -- Trace tools triggered by chat message
- (command_execution_id, created_at) -- Trace tools in command execution
- (conversation_id, created_at) -- Analyze tool usage in conversation
```

## Integration Points & Context Flow

### Chat → Command → Tool Flow
```
1. User sends chat message (message_id, conversation_id, correlation_id)
2. Chat triggers command execution (execution_id inherits correlation_id)
3. Command DSL step invokes tool (tool inherits all correlation context)
```

### Fragment Processing → Tool Flow
```
1. Fragment processing job starts (job_id, correlation_id, fragment_id)
2. Processing step needs tool (tool inherits processing context)
3. Tool invocation links to fragment processing pipeline
```

### Direct Tool Invocation Flow
```
1. API request invokes tool directly (correlation_id from middleware)
2. Tool invocation records correlation context
3. Debugging can trace from request to tool execution
```

## Query Patterns & Use Cases

### Debugging Scenarios
1. **"What tools were triggered by this chat message?"**
   ```sql
   SELECT * FROM tool_invocations WHERE message_id = ?
   ```

2. **"What happened in this request?"**
   ```sql
   SELECT * FROM tool_invocations WHERE correlation_id = ?
   ```

3. **"Which tools are used in this conversation?"**
   ```sql
   SELECT tool_slug, COUNT(*) FROM tool_invocations 
   WHERE conversation_id = ? GROUP BY tool_slug
   ```

4. **"What tools failed during this command execution?"**
   ```sql
   SELECT * FROM tool_invocations 
   WHERE command_execution_id = ? AND status = 'error'
   ```

### Analytics Scenarios
1. **Tool usage patterns by conversation**
2. **Command execution success rates with tool dependencies**
3. **Fragment processing bottlenecks with tool timing**
4. **Tool failure correlation with upstream context**

## Performance & Storage Considerations

### Additional Storage Impact
- **5 new nullable string fields** per tool invocation
- **4 new indexes** for correlation queries
- **Estimated 200-500 bytes** additional storage per invocation

### Query Performance
- Correlation queries will be frequent for debugging
- Need efficient indexes on correlation fields
- Consider composite indexes for common query patterns

### Data Retention
- Tool invocations with correlation data for debugging
- Consider archiving old correlation data
- Maintain referential integrity with related systems