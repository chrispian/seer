# Tool-Aware Turn - Enhancement Tasks

**Date**: 2025-10-09
**Status**: In Progress

## Enhancement 1: Auto-refresh MCP Tool Cache

### Current State
- ❌ No automatic refresh of MCP tool definitions
- Must manually run `php artisan tools:sync-mcp`
- No staleness detection

### Proposed Solution
Auto-refresh on stale cache with async queue job (non-blocking)

**Implementation**:
1. Add staleness check in `ToolSelector::getToolsSliceForGoal()`
2. Dispatch `RefreshMcpToolsJob` if cache older than threshold
3. Continue using current cache (don't block user)
4. Add config for `mcp_cache_ttl_hours`

**Effort**: ~30 minutes

**Benefits**:
- Always fresh tool definitions
- Zero impact on user experience
- Automatic maintenance

---

## Enhancement 2: Streaming Tool Responses

### Current State
- ❌ Tools return complete response (no streaming)
- User waits for entire pipeline to complete
- No progress indicators

### Proposed Solution
Stream pipeline progress via SSE

**Implementation** (2-4 hours):

1. **Modify ToolRunner** to yield incremental results (~1 hour)
   ```php
   public function executeStreaming(ToolPlan $plan): \Generator
   {
       foreach ($plan->plan_steps as $step) {
           $result = $this->executeSingleTool(...);
           yield $result;
       }
   }
   ```

2. **Update Pipeline** for streaming mode (~1 hour)
   ```php
   public function executeStreaming($sessionId, $userMessage): \Generator
   {
       yield ['type' => 'context', 'data' => $context];
       yield ['type' => 'decision', 'data' => $decision];
       yield ['type' => 'plan', 'data' => $plan];
       foreach ($toolRunner->executeStreaming($plan) as $result) {
           yield ['type' => 'tool_result', 'data' => $result];
       }
       yield ['type' => 'summary', 'data' => $summary];
       yield ['type' => 'final', 'data' => $message];
   }
   ```

3. **Update ChatApiController** for SSE streaming (~1 hour)
   ```php
   return response()->stream(function() use ($pipeline) {
       foreach ($pipeline->executeStreaming(...) as $event) {
           echo "data: " . json_encode($event) . "\n\n";
           flush();
       }
   });
   ```

4. **Frontend handling** (~30 min)
   - Remove `skip_stream: true` for tool-aware
   - Use existing EventSource pattern
   - Display progress updates

**Complexity**: Medium

**Benefits**:
- User sees progress in real-time
- Better UX for slow tools
- Perceived performance improvement
- Matches existing chat UX

---

## Task Execution Order

1. ✅ Task 1: Auto-refresh MCP cache (30 min)
2. ⏳ Task 2: Streaming responses (2-4 hours)

---

**Current Status**: Starting Task 1
