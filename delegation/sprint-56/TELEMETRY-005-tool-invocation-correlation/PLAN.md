# TELEMETRY-005: Enhanced Tool Invocation Correlation - Implementation Plan

## Estimated Time: 6 hours

## Phase 1: Database Schema Enhancement (1.5 hours)

### 1.1 Create Migration for Correlation Fields
**File**: `database/migrations/2025_01_04_add_correlation_fields_to_tool_invocations.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_invocations', function (Blueprint $table) {
            // Chat correlation fields
            $table->string('message_id')->nullable()->after('fragment_id');
            $table->string('conversation_id')->nullable()->after('message_id');
            
            // Command execution correlation
            $table->string('command_execution_id')->nullable()->after('conversation_id');
            
            // Request correlation
            $table->string('correlation_id')->nullable()->after('command_execution_id');
            
            // Fragment processing correlation
            $table->string('processing_job_id')->nullable()->after('correlation_id');
            
            // Add indexes for correlation queries
            $table->index(['correlation_id', 'created_at']);
            $table->index(['message_id', 'created_at']);
            $table->index(['command_execution_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['processing_job_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tool_invocations', function (Blueprint $table) {
            $table->dropIndex(['correlation_id', 'created_at']);
            $table->dropIndex(['message_id', 'created_at']);
            $table->dropIndex(['command_execution_id', 'created_at']);
            $table->dropIndex(['conversation_id', 'created_at']);
            $table->dropIndex(['processing_job_id', 'created_at']);
            
            $table->dropColumn([
                'message_id',
                'conversation_id', 
                'command_execution_id',
                'correlation_id',
                'processing_job_id'
            ]);
        });
    }
};
```

### 1.2 Create Tool Invocation Model (Optional)
**File**: `app/Models/ToolInvocation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ToolInvocation extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id',
        'tool_slug',
        'command_slug',
        'fragment_id',
        'message_id',
        'conversation_id',
        'command_execution_id',
        'correlation_id',
        'processing_job_id',
        'request',
        'response',
        'status',
        'duration_ms'
    ];
    
    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'created_at' => 'datetime'
    ];
    
    // Correlation query scopes
    public function scopeByCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }
    
    public function scopeByMessage($query, string $messageId)
    {
        return $query->where('message_id', $messageId);
    }
    
    public function scopeByCommandExecution($query, string $executionId)
    {
        return $query->where('command_execution_id', $executionId);
    }
    
    public function scopeByConversation($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }
}
```

## Phase 2: Tool Invocation Enhancement Service (1.5 hours)

### 2.1 Create Enhanced Tool Invocation Logger
**File**: `app/Services/Telemetry/ToolInvocationLogger.php`

```php
<?php

namespace App\Services\Telemetry;

use App\Services\Telemetry\CorrelationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolInvocationLogger
{
    public static function log(array $data): string
    {
        $invocationId = (string) Str::uuid();
        
        // Get correlation context from current request/job
        $correlationContext = self::gatherCorrelationContext($data);
        
        DB::table('tool_invocations')->insert([
            'id' => $invocationId,
            'user_id' => $data['user_id'] ?? auth()->id() ?? 1,
            'tool_slug' => $data['tool_slug'],
            'command_slug' => $data['command_slug'] ?? null,
            'fragment_id' => $data['fragment_id'] ?? null,
            
            // Enhanced correlation fields
            'message_id' => $correlationContext['message_id'] ?? null,
            'conversation_id' => $correlationContext['conversation_id'] ?? null,
            'command_execution_id' => $correlationContext['command_execution_id'] ?? null,
            'correlation_id' => $correlationContext['correlation_id'] ?? null,
            'processing_job_id' => $correlationContext['processing_job_id'] ?? null,
            
            'request' => json_encode($data['request'] ?? null),
            'response' => json_encode($data['response'] ?? null),
            'status' => $data['status'] ?? 'ok',
            'duration_ms' => $data['duration_ms'] ?? null,
            'created_at' => now(),
        ]);
        
        return $invocationId;
    }
    
    private static function gatherCorrelationContext(array $data): array
    {
        $context = [];
        
        // Get correlation ID from middleware (TELEMETRY-001)
        $context['correlation_id'] = CorrelationContext::get() ?? $data['correlation_id'] ?? null;
        
        // Get command execution context if available (TELEMETRY-004)
        if (isset($data['command_execution_id'])) {
            $context['command_execution_id'] = $data['command_execution_id'];
        }
        
        // Get chat context if available (TELEMETRY-002)
        if (isset($data['message_id'])) {
            $context['message_id'] = $data['message_id'];
        }
        
        if (isset($data['conversation_id'])) {
            $context['conversation_id'] = $data['conversation_id'];
        }
        
        // Get processing job context if available (TELEMETRY-003)
        if (isset($data['processing_job_id'])) {
            $context['processing_job_id'] = $data['processing_job_id'];
        }
        
        return array_filter($context);
    }
    
    public static function queryByCorrelation(string $correlationId): array
    {
        return DB::table('tool_invocations')
            ->where('correlation_id', $correlationId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
    
    public static function queryByMessage(string $messageId): array
    {
        return DB::table('tool_invocations')
            ->where('message_id', $messageId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
    
    public static function queryByCommandExecution(string $executionId): array
    {
        return DB::table('tool_invocations')
            ->where('command_execution_id', $executionId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
}
```

## Phase 3: Update Tool Invocation Points (2 hours)

### 3.1 Update ToolCallStep with Enhanced Logging
**File**: `app/Services/Commands/DSL/Steps/ToolCallStep.php`

Replace existing tool invocation logging (around line 85):

```php
use App\Services\Telemetry\ToolInvocationLogger;

// Replace existing DB::table('tool_invocations')->insert() with:
$invocationId = ToolInvocationLogger::log([
    'tool_slug' => $toolSlug,
    'command_slug' => $commandSlug,
    'fragment_id' => $fragmentId,
    'command_execution_id' => $context['execution_id'] ?? null, // From TELEMETRY-004
    'message_id' => $context['message_id'] ?? null, // From chat context
    'conversation_id' => $context['conversation_id'] ?? null, // From chat context
    'request' => $request,
    'response' => $response,
    'status' => $success ? 'ok' : 'error',
    'duration_ms' => $duration,
]);
```

### 3.2 Update Chat-Triggered Tool Context
**File**: `app/Http/Controllers/ChatApiController.php` (if tools are invoked directly)

Ensure chat context propagates to any direct tool invocations:

```php
// When dispatching commands that might invoke tools, pass chat context
$commandContext = [
    'message_id' => $messageId,
    'conversation_id' => $conversationId,
    'session_id' => $sessionId
];
```

### 3.3 Update Fragment Processing Tool Context
**File**: `app/Jobs/ProcessFragmentJob.php` 

Ensure processing job context propagates to tool invocations:

```php
// In job handle method, add processing context for tool invocations
$processingContext = [
    'processing_job_id' => $jobId, // From TELEMETRY-003
    'fragment_id' => $this->fragment->id
];
```

## Phase 4: Query Interface & Console Commands (1 hour)

### 4.1 Enhance Existing Tool Invocations Command
**File**: `app/Console/Commands/Tools/ToolInvocationsCommand.php`

Add correlation query options:

```php
// Add to existing command options
->option('correlation-id', null, InputOption::VALUE_REQUIRED, 'Filter by correlation ID')
->option('message-id', null, InputOption::VALUE_REQUIRED, 'Filter by message ID')
->option('command-execution-id', null, InputOption::VALUE_REQUIRED, 'Filter by command execution ID')
->option('conversation-id', null, InputOption::VALUE_REQUIRED, 'Filter by conversation ID')

public function handle()
{
    $query = DB::table('tool_invocations')
        ->orderBy('created_at', 'desc');
    
    // Add correlation filters
    if ($correlationId = $this->option('correlation-id')) {
        $query->where('correlation_id', $correlationId);
    }
    
    if ($messageId = $this->option('message-id')) {
        $query->where('message_id', $messageId);
    }
    
    if ($executionId = $this->option('command-execution-id')) {
        $query->where('command_execution_id', $executionId);
    }
    
    if ($conversationId = $this->option('conversation-id')) {
        $query->where('conversation_id', $conversationId);
    }
    
    // ... existing logic with enhanced display
    
    $this->table([
        'ID', 'Tool', 'Status', 'Duration', 'Created',
        'Message ID', 'Command Exec ID', 'Correlation ID'
    ], $invocations->map(function ($invocation) {
        return [
            Str::limit($invocation->id, 8),
            $invocation->tool_slug,
            $invocation->status,
            $invocation->duration_ms ? round($invocation->duration_ms, 1) . 'ms' : '-',
            $invocation->created_at->format('H:i:s'),
            Str::limit($invocation->message_id ?? '-', 8),
            Str::limit($invocation->command_execution_id ?? '-', 8),
            Str::limit($invocation->correlation_id ?? '-', 8)
        ];
    }));
}
```

### 4.2 Create Correlation Analysis Command
**File**: `app/Console/Commands/Telemetry/AnalyzeCorrelationCommand.php`

```php
<?php

namespace App\Console\Commands\Telemetry;

use App\Services\Telemetry\ToolInvocationLogger;
use Illuminate\Console\Command;

class AnalyzeCorrelationCommand extends Command
{
    protected $signature = 'telemetry:analyze 
                           {correlation-id : Correlation ID to analyze}
                           {--format=table : Output format (table|json)}';
    
    protected $description = 'Analyze all activity for a correlation ID';
    
    public function handle()
    {
        $correlationId = $this->argument('correlation-id');
        
        // Get tool invocations
        $toolInvocations = ToolInvocationLogger::queryByCorrelation($correlationId);
        
        // Get command executions (from logs or database)
        // Get chat messages (from logs or database)
        // Get fragment processing (from logs)
        
        if ($this->option('format') === 'json') {
            $this->line(json_encode([
                'correlation_id' => $correlationId,
                'tool_invocations' => $toolInvocations,
                // other correlated data
            ], JSON_PRETTY_PRINT));
        } else {
            $this->info("Correlation Analysis for: {$correlationId}");
            
            if (!empty($toolInvocations)) {
                $this->info("\nTool Invocations:");
                $this->table(['Time', 'Tool', 'Status', 'Duration'], 
                    collect($toolInvocations)->map(function ($inv) {
                        return [
                            $inv->created_at,
                            $inv->tool_slug,
                            $inv->status,
                            $inv->duration_ms ? round($inv->duration_ms, 1) . 'ms' : '-'
                        ];
                    }));
            }
        }
    }
}
```

## Phase 5: Testing & Validation (1 hour)

### 5.1 Unit Tests for ToolInvocationLogger
**File**: `tests/Unit/Services/Telemetry/ToolInvocationLoggerTest.php`

```php
<?php

namespace Tests\Unit\Services\Telemetry;

use App\Services\Telemetry\ToolInvocationLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToolInvocationLoggerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_logs_tool_invocation_with_correlation()
    {
        $invocationId = ToolInvocationLogger::log([
            'tool_slug' => 'test.tool',
            'command_slug' => 'test.command',
            'fragment_id' => 123,
            'command_execution_id' => 'exec-123',
            'message_id' => 'msg-456',
            'correlation_id' => 'corr-789',
            'request' => ['param' => 'value'],
            'response' => ['result' => 'success'],
            'status' => 'ok',
            'duration_ms' => 150.5
        ]);
        
        $this->assertIsString($invocationId);
        
        $stored = DB::table('tool_invocations')->where('id', $invocationId)->first();
        
        $this->assertEquals('test.tool', $stored->tool_slug);
        $this->assertEquals('exec-123', $stored->command_execution_id);
        $this->assertEquals('msg-456', $stored->message_id);
        $this->assertEquals('corr-789', $stored->correlation_id);
        $this->assertEquals('ok', $stored->status);
        $this->assertEquals(150.5, $stored->duration_ms);
    }
    
    public function test_query_by_correlation()
    {
        // Insert test data
        ToolInvocationLogger::log([
            'tool_slug' => 'tool1',
            'correlation_id' => 'test-corr-id'
        ]);
        
        ToolInvocationLogger::log([
            'tool_slug' => 'tool2', 
            'correlation_id' => 'test-corr-id'
        ]);
        
        $results = ToolInvocationLogger::queryByCorrelation('test-corr-id');
        
        $this->assertCount(2, $results);
        $this->assertEquals('tool1', $results[0]->tool_slug);
        $this->assertEquals('tool2', $results[1]->tool_slug);
    }
}
```

### 5.2 Integration Tests for Tool Correlation
**File**: `tests/Feature/Telemetry/ToolInvocationCorrelationTest.php`

```php
<?php

namespace Tests\Feature\Telemetry;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToolInvocationCorrelationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_command_execution_correlates_to_tool_invocations()
    {
        // Execute a command that invokes tools
        $response = $this->postJson('/api/commands/execute', [
            'command' => 'test.command.with.tools'
        ]);
        
        $response->assertOk();
        $executionId = $response->json('execution_id');
        
        // Verify tool invocations are correlated
        $toolInvocations = DB::table('tool_invocations')
            ->where('command_execution_id', $executionId)
            ->get();
            
        $this->assertGreaterThan(0, $toolInvocations->count());
        
        foreach ($toolInvocations as $invocation) {
            $this->assertEquals($executionId, $invocation->command_execution_id);
            $this->assertNotNull($invocation->correlation_id);
        }
    }
}
```

## Implementation Checklist

- [ ] Create migration for correlation fields
- [ ] Create optional ToolInvocation model
- [ ] Create ToolInvocationLogger service
- [ ] Update ToolCallStep with enhanced logging
- [ ] Update tool invocation context propagation
- [ ] Enhance existing tool invocations console command
- [ ] Create correlation analysis console command
- [ ] Unit tests for ToolInvocationLogger
- [ ] Integration tests for tool correlation
- [ ] Performance tests for correlation queries

## Success Metrics

- Tool invocations correlate to chat messages, commands, and fragments
- Database queries for correlation analysis are efficient (<100ms)
- Backward compatibility maintained for existing tool invocation data
- <1ms overhead per tool invocation for correlation logging
- Complete traceability from UI action to tool execution