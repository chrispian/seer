# ENG-10-04: Chat Integration & Streaming

## Agent Profile
**Type**: Full-Stack Engineer
**Expertise**: Laravel controllers, SSE streaming, real-time systems, WebSocket alternatives
**Focus**: API integration, streaming implementation, state management

## Mission
Integrate tool execution into the chat flow with real-time streaming of results, modifying ChatApiController to handle tool commands and stream outputs back to the interface.

## Current Context
- ChatApiController handles regular messages and AI streaming
- Existing SSE streaming infrastructure for AI responses
- Tool executor will provide results (ENG-10-03)
- Need to integrate tool flow into existing chat pipeline

## Skills Required
- Laravel controller modification
- Server-Sent Events (SSE) implementation
- Stream handling and buffering
- Fragment creation and management
- Error propagation in streams

## Success Metrics
- Tool commands execute from chat interface
- Real-time streaming of tool output
- Fragments created for tool interactions
- Errors displayed gracefully in chat
- No regression in existing chat functionality
- Streaming latency <50ms per chunk

## Deliverables

### 1. Modified ChatApiController
```php
// Add to send() method
public function send(Request $req)
{
    // Existing validation...
    
    // Tool command detection
    $detector = app(ToolCommandDetector::class);
    $commandType = $detector->detectCommandType($data['content']);
    
    if ($commandType === CommandType::MCP_TOOL) {
        return $this->handleToolCommand($data);
    }
    
    // Existing AI flow...
}

private function handleToolCommand(array $data): JsonResponse
{
    // Tool execution logic
}
```

### 2. Tool Streaming Endpoint
```php
public function streamTool(string $executionId): StreamedResponse
{
    return new StreamedResponse(function () use ($executionId) {
        // Stream tool output
    });
}
```

### 3. Tool Fragment Creation
```php
class CreateToolFragment
{
    public function __invoke(ToolResult $result): Fragment
    {
        // Create fragment for tool execution
    }
}
```

### 4. Stream Response Formatter
```php
class ToolStreamFormatter
{
    public function formatChunk($data): string
    public function formatError($error): string
    public function formatCompletion($result): string
}
```

## Technical Approach

### Integration Flow
```
Chat Message → Detect Tool → Execute Tool → Stream Output → Create Fragment
                                ↓
                          Queue Execution → Stream Progress
```

### Tool Command Handler
```php
private function handleToolCommand(array $data): JsonResponse
{
    $executionId = Str::uuid();
    
    // Parse tool command
    $toolCall = app(ToolCommandDetector::class)->extractToolCall($data['content']);
    
    // Create user fragment
    $userFragment = app(CreateChatFragment::class)($data['content']);
    
    // Queue tool execution
    dispatch(new ExecuteToolCommand(
        executionId: $executionId,
        toolCall: $toolCall,
        sessionId: $data['session_id'],
        conversationId: $data['conversation_id'],
        userFragmentId: $userFragment->id
    ));
    
    return response()->json([
        'execution_id' => $executionId,
        'type' => 'tool_execution',
        'stream_url' => "/api/chat/stream-tool/{$executionId}"
    ]);
}
```

### Streaming Implementation
```php
public function streamTool(string $executionId): StreamedResponse
{
    return new StreamedResponse(function () use ($executionId) {
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', 0);
        
        $execution = Cache::get("tool_execution_{$executionId}");
        
        // Stream progress updates
        while (!$execution->isComplete()) {
            if ($chunk = $execution->getNextChunk()) {
                echo "data: " . json_encode([
                    'type' => 'tool_output',
                    'content' => $chunk
                ]) . "\n\n";
                @ob_flush();
                @flush();
            }
            usleep(10000); // 10ms
            $execution = Cache::get("tool_execution_{$executionId}");
        }
        
        // Stream final result
        echo "data: " . json_encode([
            'type' => 'tool_complete',
            'result' => $execution->getResult()
        ]) . "\n\n";
        
        // Create tool fragment
        $this->createToolFragment($execution);
        
        echo "data: " . json_encode(['type' => 'done']) . "\n\n";
        @ob_flush();
        @flush();
    });
}
```

### Fragment Creation
```php
private function createToolFragment($execution): Fragment
{
    $result = $execution->getResult();
    
    return app(CreateToolFragment::class)([
        'content' => $this->formatToolOutput($result),
        'metadata' => [
            'type' => 'tool_execution',
            'tool' => $result->tool,
            'parameters' => $result->parameters,
            'execution_time' => $result->executionTime,
            'success' => $result->success,
        ],
        'conversation_id' => $execution->conversationId,
        'session_id' => $execution->sessionId,
    ]);
}
```

## Error Handling

### Stream Error Format
```php
private function streamError($error): void
{
    echo "data: " . json_encode([
        'type' => 'tool_error',
        'error' => [
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'recoverable' => $this->isRecoverable($error),
        ]
    ]) . "\n\n";
}
```

### Graceful Degradation
- Show error in chat with retry option
- Log detailed error for debugging
- Maintain chat session continuity
- Offer alternative suggestions

## Frontend Integration Points
```javascript
// React component handling
const handleToolStream = async (executionId) => {
    const eventSource = new EventSource(`/api/chat/stream-tool/${executionId}`);
    
    eventSource.onmessage = (event) => {
        const data = JSON.parse(event.data);
        
        switch(data.type) {
            case 'tool_output':
                appendToChat(data.content);
                break;
            case 'tool_error':
                showError(data.error);
                break;
            case 'tool_complete':
                finalizeToolOutput(data.result);
                break;
        }
    };
};
```

## Testing Plan
1. Test tool command detection in chat
2. Verify streaming functionality
3. Test fragment creation
4. Error scenario testing
5. Performance testing with large outputs
6. Integration testing with frontend

## Dependencies
- Tool executor (ENG-10-03)
- Detection system (ENG-10-02)
- Existing chat infrastructure
- Fragment system
- Laravel queue system

## Time Estimate
4-6 hours total:
- 1.5 hours: Controller modifications
- 1.5 hours: Streaming implementation
- 1 hour: Fragment creation logic
- 1 hour: Error handling
- 1 hour: Testing and integration