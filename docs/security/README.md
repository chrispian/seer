# Security Guardrails System

Comprehensive security validation and approval system for AI-driven operations. Protects against dangerous commands, filesystem operations, and network requests through multi-layered security checks.

## 🎯 Overview

The security guardrails system provides:
- **Policy-based validation** - Allowlist/blocklist enforcement
- **Risk scoring** - Multi-dimensional risk assessment (0-100 scale)
- **User approval workflows** - High-risk operations require explicit consent
- **Audit logging** - Complete execution trail
- **Dry-run simulation** - Preview operations without execution
- **Resource limiting** - Timeout and memory constraints

## 📦 Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Security Pipeline                        │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  1. Policy Check      → Is operation allowed?                │
│     └─ PolicyRegistry                                        │
│                                                               │
│  2. Risk Assessment   → How dangerous is it?                 │
│     └─ RiskScorer     (0-25: low, 26-50: medium,            │
│                        51-75: high, 76-100: critical)        │
│                                                               │
│  3. Approval Gate     → User consent required?               │
│     └─ ApprovalManager (score >= 26)                         │
│                                                               │
│  4. Validation        → Safe to execute?                     │
│     ├─ ShellGuard     (command injection, arg validation)    │
│     ├─ FilesystemGuard (path traversal, symlinks)           │
│     └─ NetworkGuard   (SSRF, private IPs)                   │
│                                                               │
│  5. Execution         → Run with limits                      │
│     ├─ ResourceLimiter (timeout, memory, output)            │
│     └─ EnhancedShellExecutor (orchestration + audit)        │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## 🚀 Quick Start

### Basic Command Execution

```php
use App\Services\Security\EnhancedShellExecutor;

$executor = app(EnhancedShellExecutor::class);

// Execute with full security stack
$result = $executor->execute('git status', [
    'timeout' => 30,
    'workdir' => '/path/to/repo',
]);

if ($result['success']) {
    echo $result['stdout'];
} else if ($result['blocked'] ?? false) {
    // Security policy blocked execution
    echo "Blocked: " . implode(', ', $result['violations']);
} else {
    // Execution failed
    echo "Error: " . $result['stderr'];
}
```

### Request User Approval

```php
use App\Services\Security\ApprovalManager;

$manager = app(ApprovalManager::class);

// High-risk operation requires approval
$approval = $manager->requestApproval(
    'command',
    [
        'command' => 'rm -rf /tmp/cache/*',
        'summary' => 'Delete temporary cache files',
        'context' => ['user_id' => 1],
    ],
    'conversation-123',
    'message-456'
);

if ($approval) {
    // Show approval UI to user
    return response()->json([
        'requires_approval' => true,
        'approval_request' => $manager->formatForChat($approval),
    ]);
}
```

### Check Risk Score

```php
use App\Services\Security\RiskScorer;

$scorer = app(RiskScorer::class);

$risk = $scorer->scoreCommand('npm install', ['user_id' => 1]);

echo "Risk Level: {$risk['level']} ({$risk['score']}/100)\n";
echo "Requires Approval: " . ($risk['requires_approval'] ? 'Yes' : 'No') . "\n";
echo "\nRisk Factors:\n";
foreach ($risk['factors'] as $factor) {
    echo "  - {$factor}\n";
}
```

## 🔐 Core Components

### PolicyRegistry

Central policy management for allowlists and blocklists.

```php
$registry = app(PolicyRegistry::class);

// Check if command allowed
$result = $registry->isCommandAllowed('git status');
// ['allowed' => true, 'pattern' => 'git', ...]

// Check filesystem path
$result = $registry->isPathAllowed('/var/log/app.log', 'read');
// ['allowed' => true, ...]

// Check network domain
$result = $registry->isDomainAllowed('api.github.com');
// ['allowed' => true, ...]
```

**Key Methods:**
- `isCommandAllowed(string $command): array`
- `isPathAllowed(string $path, string $operation): array`
- `isDomainAllowed(string $domain): array`
- `isToolAllowed(string $toolId): array`

### RiskScorer

Multi-dimensional risk scoring (0-100 scale).

**Risk Levels:**
- **0-25 (low):** Auto-approve, safe operations
- **26-50 (medium):** Require approval, potentially risky
- **51-75 (high):** Require approval, dangerous operations
- **76-100 (critical):** Require approval with justification

**Key Methods:**
- `scoreCommand(string $command, array $context): array`
- `scoreFileOperation(string $path, string $operation, array $context): array`
- `scoreNetworkOperation(string $domain, array $context): array`
- `scoreToolCall(string $toolId, array $parameters): array`

### ApprovalManager

User approval workflow coordination.

**Lifecycle:**
1. `requestApproval()` - Create approval request
2. User sees approval UI
3. `approveRequest()` or `rejectRequest()` - User decision
4. `executeApproved()` - Execute with `approved` flag

**Key Methods:**
- `requestApproval(string $type, array $operation, string $conversationId, string $messageId): ?ApprovalRequest`
- `approveRequest(ApprovalRequest $approval, int $userId, string $approvalMethod): bool`
- `rejectRequest(ApprovalRequest $approval, int $userId, string $reason): bool`
- `executeApproved(ApprovalRequest $approval, array $options = []): array`

### Guards

Operation-specific validation:

#### ShellGuard
- Command injection detection
- Argument validation (blocks `rm -rf`, `git push --force`)
- Resource limits by binary type

#### FilesystemGuard
- Path normalization and traversal detection
- Symlink validation
- File size limits (default 10MB)

#### NetworkGuard
- SSRF prevention (private IPs, localhost)
- Domain allowlist enforcement
- HTTPS requirement checking
- Request/response size limits (1MB request, 10MB response)

## ⚙️ Configuration

### Security Policies (`config/security.php`)

```php
'approval' => [
    'risk_threshold' => 26,  // Require approval for score >= 26
    'auto_timeout_seconds' => 300,  // Auto-reject after 5 minutes
    
    'inline_approval' => [
        'max_words' => 100,
        'max_characters' => 500,
        'max_lines' => 15,
    ],
],

'resource_limits' => [
    'default_timeout' => 30,
    'default_memory' => '128M',
    'max_output_length' => 50000,  // 50KB
],
```

### Risk Thresholds

Defined as constants in `RiskScorer`:

```php
RiskScorer::APPROVAL_THRESHOLD = 26
RiskScorer::HIGH_RISK_THRESHOLD = 51
RiskScorer::CRITICAL_RISK_THRESHOLD = 76
```

## 📊 Audit Logging

All command executions are logged to `command_audit_logs` table:

```php
use App\Models\CommandAuditLog;

// Query audit logs
$logs = CommandAuditLog::where('user_id', auth()->id())
    ->where('status', 'completed')
    ->latest()
    ->get();

foreach ($logs as $log) {
    echo "{$log->command_signature}: {$log->status} ({$log->execution_time_ms}ms)\n";
}
```

## 🧪 Testing & Debugging

### Dry-Run Simulation

Preview operations without execution:

```php
use App\Services\Security\DryRunSimulator;

$simulator = app(DryRunSimulator::class);

$result = $simulator->simulateCommand('rm -rf /tmp/cache', [
    'user_id' => 1,
]);

// Result:
// [
//   'would_execute' => false,
//   'policy_check' => ['allowed' => true, ...],
//   'risk_assessment' => ['score' => 85, 'level' => 'critical', ...],
//   'predicted_changes' => [
//     ['type' => 'file_delete', 'description' => 'Would delete files'],
//   ],
//   'warnings' => ['REQUIRES APPROVAL: Risk score 85 (critical)'],
// ]
```

### Dry-Run via Executor

```php
$executor = app(EnhancedShellExecutor::class);

$preview = $executor->dryRun('git push --force');

// Result:
// [
//   'would_execute' => false,
//   'validation' => [...],
//   'resource_limits' => ['timeout' => 120, 'memory' => '512M'],
// ]
```

## 🔒 Security Best Practices

### 1. Always Validate Before Execution

```php
// ❌ Bad - Direct execution
exec($userCommand);

// ✅ Good - Full security stack
$executor->execute($userCommand, ['context' => $context]);
```

### 2. Pass Approval Context

```php
// After user approves
$result = $executor->execute($command, [
    'context' => ['approved' => true],  // Skip policy/risk checks
]);
```

### 3. Check Return Values

```php
$result = $executor->execute($command);

if ($result['blocked'] ?? false) {
    // Handle security block
    log_security_event('Command blocked', $result['violations']);
} else if (!$result['success']) {
    // Handle execution failure
    handle_error($result['stderr']);
}
```

### 4. Use Appropriate Timeouts

```php
// Long-running package operations
$executor->execute('npm install', ['timeout' => 300]);

// Quick git commands
$executor->execute('git status', ['timeout' => 30]);
```

## 📚 Additional Resources

- **[CODE_QUALITY_IMPROVEMENT_PLAN.md](./CODE_QUALITY_IMPROVEMENT_PLAN.md)** - Documentation standards
- **[PHPSTAN_ANALYSIS_SUMMARY.md](./PHPSTAN_ANALYSIS_SUMMARY.md)** - Static analysis results
- **API Documentation:** See PHPDoc in each service class
- **Risk Scoring Logic:** See `RiskScorer` class documentation
- **Approval UI:** See `ApprovalController` and frontend components

## 🐛 Troubleshooting

### Command Always Requires Approval

Check the risk score factors:

```php
$risk = $scorer->scoreCommand($command);
print_r($risk['factors']);
```

Common high-risk factors:
- Shell execution base score: +35
- Dangerous patterns (sudo, rm -rf): +40
- Sensitive paths (.ssh, .env): +30

### Approval UI Not Showing

1. Check if approval was created:
   ```php
   $approval = $manager->requestApproval(...);
   if (!$approval) {
       // Score < 26, auto-approved
   }
   ```

2. Check approval timeout:
   - Approvals auto-reject after 5 minutes
   - Check `status` field: pending, approved, rejected, expired

### Command Blocked by Policy

Check which policy matched:

```php
$result = $registry->isCommandAllowed($command);
echo $result['reason'];  // "Command not in allowlist"
echo $result['pattern']; // null (no match)
```

Add to allowlist in `security_policies` table or via seeder.

## 🤝 Contributing

When adding new security features:

1. ✅ Add comprehensive PHPDoc
2. ✅ Use `declare(strict_types=1)`
3. ✅ Extract magic values to constants
4. ✅ Add usage examples
5. ✅ Run PHPStan level 6+
6. ✅ Update this README

## 📝 License

Part of the Seer project. See main LICENSE file.
