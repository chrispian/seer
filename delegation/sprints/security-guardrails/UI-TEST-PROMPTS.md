# Security Approval Flow - UI Test Prompts

## 🧪 Ready for Testing!

The approval workflow is now integrated into the chat UI. Use these prompts to test different scenarios.

---

## Test 1: High-Risk Command (Should Trigger Approval)

**Prompt to type in chat:**
```
:exec-tool rm -rf /tmp/test-logs
```

**Expected Behavior:**
1. Message sends
2. Assistant responds with:
   - Warning message about the operation
   - Risk badge showing "High (60/100)"
   - Risk factors listed (e.g., "Recursive force delete: +40")
   - **[✓ Approve] [✗ Reject]** buttons visible
3. No execution happens yet
4. Buttons are clickable

**Then click [✓ Approve]:**
- Buttons disappear
- Shows "✓ Approved by user at [time]"
- Command executes
- Results appear in chat

**OR click [✗ Reject]:**
- Buttons disappear
- Shows "✗ Rejected by user at [time]"
- Command does NOT execute

---

## Test 2: Low-Risk Command (Auto-Approve, No Buttons)

**Prompt:**
```
:exec-tool ls -la
```

**Expected Behavior:**
1. Message sends
2. Command executes immediately (no approval needed)
3. Results appear (directory listing)
4. No approval buttons shown
5. Risk score: 20 (low)

---

## Test 3: Blocked Command (Policy Denial)

**Prompt:**
```
:exec-tool sudo apt-get install nginx
```

**Expected Behavior:**
1. Message sends
2. Assistant responds with error:
   - "BLOCKED: Matched deny rule"
   - No execution
   - No approval buttons (blocked before risk assessment)

---

## Test 4: Another High-Risk (Path Traversal Attempt)

**Prompt:**
```
:exec-tool cat /workspace/../etc/passwd
```

**Expected Behavior:**
1. Should be blocked by FilesystemGuard
2. Error: "Path traversal patterns (..) not allowed"
3. No execution

---

## Test 5: Medium-Risk Command

**Prompt:**
```
:exec-tool git push origin test-branch
```

**Expected Behavior:**
- Depends on risk score (likely 25-30 = medium)
- Should get "log_and_approve" action
- May or may not show approval buttons
- If score >= 51, approval buttons appear

---

## What to Look For

### ✅ Success Indicators:
- High-risk commands show approval buttons
- Buttons are inline with the message
- Risk badge is color-coded (orange/red for high/critical)
- Clicking Approve makes buttons disappear
- Approved status shows timestamp
- Command executes after approval
- Low-risk commands execute immediately

### ❌ Issues to Watch For:
- Buttons don't appear when they should
- Buttons don't disappear after clicking
- Command executes without approval
- Errors in console
- UI doesn't update after approve/reject

---

## Debug Commands (If Issues)

**Check if approval was created:**
```bash
php artisan tinker --execute="
\$recent = App\Models\ApprovalRequest::latest()->first();
if (\$recent) {
    echo 'Latest approval: ' . \$recent->operation_summary . PHP_EOL;
    echo 'Status: ' . \$recent->status . PHP_EOL;
    echo 'Risk: ' . \$recent->risk_score . PHP_EOL;
}
"
```

**Check audit logs:**
```bash
php artisan tinker --execute="
\$logs = App\Models\CommandAuditLog::latest()->take(5)->get(['command_name', 'status', 'exit_code']);
foreach (\$logs as \$log) {
    echo \$log->command_name . ': ' . \$log->status . PHP_EOL;
}
"
```

---

## Expected Test Results

| Command | Risk | Approval? | Execute? |
|---------|------|-----------|----------|
| `ls -la` | 20 (low) | No | Immediately |
| `rm -rf /tmp/test` | 60 (high) | Yes | After approval |
| `sudo anything` | Blocked | No | Never |
| `git push` | 25 (low) | No | Immediately |
| `/etc/passwd` access | Blocked | No | Never |

---

## Next Steps After Testing

1. **Test all scenarios above**
2. **Report any issues** (buttons not showing, errors, etc.)
3. **Verify audit logs** are being created
4. **Test natural language approval** (future feature)
5. **Test long content modal** (future feature - need to create a plan/doc)

**Start with Test 1 (rm -rf) - it's guaranteed to trigger approval!**
