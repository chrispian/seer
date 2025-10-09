# Approval Button Bug Fix - October 9, 2025

## Issue Summary

Dangerous command approval buttons were not appearing, and when they did appear, command execution results would flash briefly then disappear from the UI.

## Root Causes

### Issue 1: Buttons Not Appearing
**Problem**: Pending approval requests were being automatically set to `timeout` status when loading messages from session storage.

**Location**: `resources/js/islands/chat/ChatIsland.tsx` (lines 311-316)

**Code**:
```typescript
// Auto-timeout any pending approval requests (they're stale from previous session)
let approvalRequest = msg.approval_request
if (approvalRequest && approvalRequest.status === 'pending') {
  console.log('Auto-timing out stale pending approval:', approvalRequest.id)
  approvalRequest = { ...approvalRequest, status: 'timeout' }
}
```

**Why it failed**: The `ApprovalButtonSimple` component returns `null` for `timeout` status, so buttons never rendered.

**Fix**: Removed the auto-timeout logic entirely. Pending approvals should remain pending until explicitly approved/rejected.

---

### Issue 2: Approved Commands Not Executing
**Problem**: Security policy check was blocking approved commands even with `approved: true` flag.

**Location**: `app/Services/Security/Guards/ShellGuard.php` (lines 27-33)

**Code**:
```php
$policyDecision = $this->policyRegistry->isCommandAllowed($command);
$validation['policy_decision'] = $policyDecision;

if (!$policyDecision['allowed']) {
    $validation['violations'][] = $policyDecision['reason'];
    return $validation; // ❌ Returns early, ignores approval flag
}
```

**Why it failed**: Policy check happened BEFORE the approval flag check. Commands without matching policies were denied even when explicitly approved by user.

**Fix**: Check the `approved` flag first and skip policy validation when true:
```php
// Skip approval check if already approved (via context flag)
$alreadyApproved = $context['approved'] ?? false;

// If already approved, skip policy check (user explicitly approved)
if (!$alreadyApproved) {
    $policyDecision = $this->policyRegistry->isCommandAllowed($command);
    $validation['policy_decision'] = $policyDecision;

    if (!$policyDecision['allowed']) {
        $validation['violations'][] = $policyDecision['reason'];
        return $validation;
    }
}
```

---

### Issue 3: Execution Results Disappearing (The Big One!)
**Problem**: Command execution results would flash on screen for a split second then disappear.

**Location**: `resources/js/islands/chat/ChatIsland.tsx`

**Why it failed**:
1. User clicks Approve button
2. Frontend updates local React state with `executionResult`
3. Frontend saves messages to session via `saveMessagesToSession()`
4. Saving triggers `sessionDetailsQuery` to refetch from database
5. Database reload overwrites local state WITHOUT execution result (because it wasn't saved!)
6. React re-renders with reloaded data, execution result disappears

**The Data Flow**:
```
Click Approve
  ↓
Local State: { executionResult: {...} } ✓
  ↓
Save to Session: { approval_request: {...} } ❌ Missing executionResult!
  ↓
Session Refetch Triggered
  ↓
Load from Session: { approval_request: {...} } ❌ No executionResult!
  ↓
setMessages() overwrites local state
  ↓
Execution result GONE! 💥
```

**Fix Part 1**: Save execution result to session (line 350)
```typescript
const sessionMessages = updatedMessages.map(msg => ({
  id: msg.messageId || msg.id,
  type: msg.role,
  message: msg.md,
  fragment_id: msg.fragmentId,
  is_bookmarked: msg.isBookmarked,
  approval_request: msg.approvalRequest,
  execution_result: msg.executionResult, // ✓ Added this line
  created_at: new Date().toISOString(),
}))
```

**Fix Part 2**: Restore execution result when loading from session (line 319)
```typescript
return {
  id: messageKey,
  role: msg.type === 'user' ? 'user' : 'assistant',
  md: msg.message || '',
  messageId: msg.id,
  fragmentId: msg.fragment_id,
  isBookmarked: msg.is_bookmarked,
  approvalRequest: msg.approval_request,
  executionResult: msg.execution_result, // ✓ Added this line
}
```

---

## Files Modified

### Frontend
1. **resources/js/islands/chat/ChatIsland.tsx**
   - Removed auto-timeout logic for pending approvals
   - Added `execution_result` to session save
   - Added `executionResult` to session load
   - Added debug logging throughout approval flow

2. **resources/js/islands/chat/ChatTranscript.tsx**
   - Consolidated duplicate `ApprovalButtonSimple` renders into single component
   - Added debug logging for execution result rendering

3. **resources/js/components/security/ApprovalButtonSimple.tsx**
   - Added TypeScript interface for props
   - Added null checks for optional date fields
   - Added debug logging

### Backend
4. **app/Services/Security/Guards/ShellGuard.php**
   - Moved approval flag check before policy check
   - Skip policy validation when command is already approved

5. **app/Http/Controllers/Api/ApprovalController.php**
   - Added extensive logging for execution flow
   - Verified command execution logic (was already correct)

6. **app/Services/Security/ApprovalManager.php**
   - No changes needed (formatForChat was already correct)

---

## Testing Procedure

1. **Test approval buttons appear**:
   ```
   :exec-tool ls -asl
   ```
   - ✓ Approval request should appear with Approve/Reject buttons
   - ✓ No "Auto-timing out" messages in logs

2. **Test command execution**:
   - Click "Approve" button
   - ✓ Command executes (check laravel.log for "Command executed" with success)
   - ✓ Execution result displays in chat
   - ✓ Result persists after page refresh

3. **Test policy bypass**:
   - Run command without matching security policy (e.g., `:exec-tool list`)
   - Approve the request
   - ✓ Command executes despite no policy match

4. **Test persistence**:
   - Approve a command
   - Refresh the browser (F5)
   - ✓ Approval status and execution result both still visible

---

## Key Learnings

### React State Management
- **Always save ephemeral UI state to persistent storage** when using session/database reload patterns
- Watch for race conditions between local state updates and server-side data fetching
- Use `useEffect` dependencies carefully - they can trigger unexpected reloads

### Security Architecture
- **Explicit approval should override policy checks** - user consent is the highest authority
- Context flags (`approved: true`) should be checked early in validation pipeline
- Order of validation matters: approval → policy → risk → injection

### Debugging Techniques
1. Add logging at state boundaries (before/after updates)
2. Check browser console AND server logs simultaneously
3. Look for "flash and disappear" patterns - indicates state overwrite issues
4. Trace data flow from user action → API → state → render

---

## Related Issues

- This bug was introduced when an agent refactored the approval system
- The working version from `delegation/error.log` showed the correct code structure
- Multiple agents worked on this, causing regression

---

## Prevention

1. **Add integration tests** for approval flow end-to-end
2. **Document session persistence patterns** for future developers
3. **Add TypeScript types** for session message structure to catch missing fields
4. **Consider moving to optimistic updates** to avoid refetch race conditions

---

## Performance Notes

The current implementation triggers a session refetch after every approval. Consider:
- Using optimistic updates without immediate refetch
- Debouncing session saves
- Using React Query's mutation callbacks to update cache directly

---

## Commit Message

```
fix(security): resolve approval button visibility and execution result persistence

- Remove auto-timeout logic that prevented buttons from appearing
- Skip policy checks for explicitly approved commands
- Persist execution results to session storage
- Restore execution results when loading from session
- Add comprehensive logging throughout approval flow

Fixes issue where approval buttons wouldn't appear and execution results
would flash briefly then disappear due to session reload race condition.
```
