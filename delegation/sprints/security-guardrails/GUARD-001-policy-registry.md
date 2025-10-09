# GUARD-001: PolicyRegistry Service

## Status: Ready to Start

## Priority: Critical (Sprint 1, Week 1)

## Problem
Security policies are currently hardcoded in various config files and classes (PermissionGate, ShellTool, etc.). This makes policies difficult to:
- Update without code changes
- Audit and review
- Test systematically
- Hot-reload without restart
- Version control and track changes

## Objectives
Create a centralized PolicyRegistry that:
1. Loads policies from YAML configuration
2. Provides a clean API for policy queries
3. Supports hot-reloading
4. Validates policy syntax
5. Logs policy decisions with reasoning

## Requirements

### Functional
1. Load policies from `config/security/policies.yaml`
2. Support multiple policy types (tools, commands, paths, domains)
3. Wildcard matching for flexible rules
4. Policy inheritance (e.g., deny overrides allow)
5. Hot-reload API endpoint (admin only)
6. Policy validation on load

### Non-Functional
- Policy lookup: < 5ms (with caching)
- Support 1000+ rules without performance degradation
- Thread-safe (Laravel is mostly single-threaded, but cache must be safe)
- Memory efficient (lazy loading)

## Implementation

### 1. Create Policy Configuration

**File:** `config/security/policies.yaml`

```yaml
version: 1.0
updated_at: "2025-10-09T13:00:00Z"

# Global settings
settings:
  default_action: deny  # deny or allow
  log_denials: true
  log_approvals: false

# Tool-level policies
tools:
  allowed:
    - shell
    - fs.*
    - mcp.*
    - gmail.*
  denied:
    - admin.*
    - system.delete

# Shell command policies
shell:
  commands:
    allowed:
      - ls
      - pwd
      - echo
      - cat
      - grep
      - find
      - git
      - npm
      - composer
      - php
    denied:
      - rm -rf
      - dd
      - mkfs
      - fdisk
  
  # Argument-level rules
  arguments:
    forbidden_patterns:
      - "--password"
      - "--secret"
      - "-P"  # ssh password
    required_flags:
      git:
        - allowed: ["status", "log", "diff", "show"]
        - denied: ["push --force", "reset --hard"]

# Filesystem policies
filesystem:
  allowed_paths:
    - /workspace
    - /tmp
    - ~/.config/fragments
  denied_paths:
    - /etc
    - /var
    - ~/.ssh
    - /System  # macOS
  
  allowed_extensions:
    - .txt
    - .md
    - .json
    - .yaml
    - .log
  
  denied_extensions:
    - .exe
    - .dll
    - .so
    - .dylib

# Network policies
network:
  allowed_domains:
    - "*.github.com"
    - "*.githubusercontent.com"
    - "api.openai.com"
    - "api.anthropic.com"
    - "*.google.com"
  
  denied_domains:
    - "*.internal"
    - "localhost"
    - "*.local"
  
  denied_ip_ranges:
    - 127.0.0.0/8
    - 10.0.0.0/8
    - 172.16.0.0/12
    - 192.168.0.0/16
    - 169.254.0.0/16  # Link-local
    - ::1/128         # IPv6 localhost

# Risk scoring weights
risk_scoring:
  base_weights:
    read_operation: 1
    write_operation: 10
    delete_operation: 25
    network_egress: 15
    shell_execution: 20
    privileged_operation: 50
  
  threshold_actions:
    0-25: auto_approve
    26-50: log_and_approve
    51-75: require_approval
    76-100: require_approval_with_justification
```

### 2. Create PolicyRegistry Service

**File:** `app/Services/Security/PolicyRegistry.php`

```php
<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class PolicyRegistry
{
    private const CACHE_KEY = 'security:policies';
    private const CACHE_TTL = 3600; // 1 hour

    private ?array $policies = null;

    public function __construct()
    {
        $this->loadPolicies();
    }

    /**
     * Check if a tool is allowed
     */
    public function isToolAllowed(string $toolId): bool
    {
        $decision = $this->evaluateToolPolicy($toolId);
        $this->logDecision('tool', $toolId, $decision);
        return $decision['allowed'];
    }

    /**
     * Check if a shell command is allowed
     */
    public function isCommandAllowed(string $command, array $args = []): bool
    {
        $decision = $this->evaluateCommandPolicy($command, $args);
        $this->logDecision('command', $command, $decision);
        return $decision['allowed'];
    }

    /**
     * Check if a filesystem path is allowed
     */
    public function isPathAllowed(string $path, string $operation = 'read'): bool
    {
        $decision = $this->evaluatePathPolicy($path, $operation);
        $this->logDecision('path', $path, $decision);
        return $decision['allowed'];
    }

    /**
     * Check if a network domain/IP is allowed
     */
    public function isDomainAllowed(string $domain): bool
    {
        $decision = $this->evaluateDomainPolicy($domain);
        $this->logDecision('domain', $domain, $decision);
        return $decision['allowed'];
    }

    /**
     * Get risk score weights
     */
    public function getRiskWeights(): array
    {
        return $this->policies['risk_scoring']['base_weights'] ?? [];
    }

    /**
     * Get threshold action for risk score
     */
    public function getThresholdAction(int $score): string
    {
        $thresholds = $this->policies['risk_scoring']['threshold_actions'] ?? [];
        
        foreach ($thresholds as $range => $action) {
            [$min, $max] = explode('-', $range);
            if ($score >= (int)$min && $score <= (int)$max) {
                return $action;
            }
        }

        return 'require_approval'; // Default to safest
    }

    /**
     * Reload policies from disk (hot-reload)
     */
    public function reload(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->policies = null;
        $this->loadPolicies();
        
        Log::info('Security policies reloaded', [
            'version' => $this->policies['version'] ?? 'unknown',
            'updated_at' => $this->policies['updated_at'] ?? null,
        ]);
    }

    /**
     * Validate policy structure
     */
    public function validate(): array
    {
        $errors = [];

        // Check required top-level keys
        $required = ['version', 'settings', 'tools', 'shell', 'filesystem', 'network', 'risk_scoring'];
        foreach ($required as $key) {
            if (!isset($this->policies[$key])) {
                $errors[] = "Missing required section: {$key}";
            }
        }

        // Validate settings
        if (isset($this->policies['settings']['default_action'])) {
            $action = $this->policies['settings']['default_action'];
            if (!in_array($action, ['allow', 'deny'])) {
                $errors[] = "Invalid default_action: {$action}";
            }
        }

        // Validate risk scoring thresholds
        if (isset($this->policies['risk_scoring']['threshold_actions'])) {
            $valid_actions = ['auto_approve', 'log_and_approve', 'require_approval', 'require_approval_with_justification'];
            foreach ($this->policies['risk_scoring']['threshold_actions'] as $action) {
                if (!in_array($action, $valid_actions)) {
                    $errors[] = "Invalid threshold action: {$action}";
                }
            }
        }

        return $errors;
    }

    /**
     * Get policy version info
     */
    public function getVersion(): array
    {
        return [
            'version' => $this->policies['version'] ?? 'unknown',
            'updated_at' => $this->policies['updated_at'] ?? null,
            'loaded_at' => $this->policies['loaded_at'] ?? now()->toIso8601String(),
        ];
    }

    // ==================== Private Methods ====================

    private function loadPolicies(): void
    {
        if ($this->policies !== null) {
            return;
        }

        $this->policies = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $policyPath = config_path('security/policies.yaml');
            
            if (!File::exists($policyPath)) {
                Log::warning('Policy file not found, using defaults', ['path' => $policyPath]);
                return $this->getDefaultPolicies();
            }

            try {
                $policies = Yaml::parseFile($policyPath);
                $policies['loaded_at'] = now()->toIso8601String();
                return $policies;
            } catch (\Exception $e) {
                Log::error('Failed to parse policy file', [
                    'path' => $policyPath,
                    'error' => $e->getMessage(),
                ]);
                return $this->getDefaultPolicies();
            }
        });
    }

    private function evaluateToolPolicy(string $toolId): array
    {
        $allowed = $this->policies['tools']['allowed'] ?? [];
        $denied = $this->policies['tools']['denied'] ?? [];

        // Check deny list first (deny takes precedence)
        if ($this->matchesPattern($toolId, $denied)) {
            return [
                'allowed' => false,
                'reason' => 'Tool explicitly denied',
                'matched_rule' => $this->findMatchingPattern($toolId, $denied),
            ];
        }

        // Check allow list
        if ($this->matchesPattern($toolId, $allowed)) {
            return [
                'allowed' => true,
                'reason' => 'Tool explicitly allowed',
                'matched_rule' => $this->findMatchingPattern($toolId, $allowed),
            ];
        }

        // Default action
        $defaultAction = $this->policies['settings']['default_action'] ?? 'deny';
        return [
            'allowed' => $defaultAction === 'allow',
            'reason' => 'Default action applied',
            'matched_rule' => null,
        ];
    }

    private function evaluateCommandPolicy(string $command, array $args): array
    {
        $shellPolicies = $this->policies['shell'] ?? [];
        $allowedCommands = $shellPolicies['commands']['allowed'] ?? [];
        $deniedCommands = $shellPolicies['commands']['denied'] ?? [];

        // Extract base command (before first space or argument)
        $baseCommand = explode(' ', trim($command))[0];

        // Check denied list
        foreach ($deniedCommands as $denied) {
            if (str_contains($command, $denied)) {
                return [
                    'allowed' => false,
                    'reason' => 'Command matches denied pattern',
                    'matched_rule' => $denied,
                ];
            }
        }

        // Check allowed list
        if (!in_array($baseCommand, $allowedCommands)) {
            return [
                'allowed' => false,
                'reason' => 'Command not in allowlist',
                'matched_rule' => null,
            ];
        }

        // Check argument-level rules
        $forbiddenPatterns = $shellPolicies['arguments']['forbidden_patterns'] ?? [];
        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($command, $pattern)) {
                return [
                    'allowed' => false,
                    'reason' => 'Command contains forbidden argument pattern',
                    'matched_rule' => $pattern,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'Command allowed',
            'matched_rule' => $baseCommand,
        ];
    }

    private function evaluatePathPolicy(string $path, string $operation): array
    {
        $fsPolicies = $this->policies['filesystem'] ?? [];
        $allowedPaths = $fsPolicies['allowed_paths'] ?? [];
        $deniedPaths = $fsPolicies['denied_paths'] ?? [];

        // Normalize path
        $path = realpath($path) ?: $path;

        // Check denied paths first
        foreach ($deniedPaths as $deniedPath) {
            if (str_starts_with($path, $deniedPath)) {
                return [
                    'allowed' => false,
                    'reason' => 'Path in denied list',
                    'matched_rule' => $deniedPath,
                ];
            }
        }

        // Check allowed paths
        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return [
                    'allowed' => true,
                    'reason' => 'Path in allowed list',
                    'matched_rule' => $allowedPath,
                ];
            }
        }

        return [
            'allowed' => false,
            'reason' => 'Path not in allowed list',
            'matched_rule' => null,
        ];
    }

    private function evaluateDomainPolicy(string $domain): array
    {
        $networkPolicies = $this->policies['network'] ?? [];
        $allowedDomains = $networkPolicies['allowed_domains'] ?? [];
        $deniedDomains = $networkPolicies['denied_domains'] ?? [];

        // Check denied first
        if ($this->matchesPattern($domain, $deniedDomains)) {
            return [
                'allowed' => false,
                'reason' => 'Domain in denied list',
                'matched_rule' => $this->findMatchingPattern($domain, $deniedDomains),
            ];
        }

        // Check allowed
        if ($this->matchesPattern($domain, $allowedDomains)) {
            return [
                'allowed' => true,
                'reason' => 'Domain in allowed list',
                'matched_rule' => $this->findMatchingPattern($domain, $allowedDomains),
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Domain not in allowed list',
            'matched_rule' => null,
        ];
    }

    private function matchesPattern(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Wildcard matching (e.g., "*.github.com" or "fs.*")
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['*', '.'], ['.*', '\\.'], $pattern) . '$/';
                if (preg_match($regex, $value)) {
                    return true;
                }
            } elseif ($value === $pattern) {
                return true;
            }
        }
        return false;
    }

    private function findMatchingPattern(string $value, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['*', '.'], ['.*', '\\.'], $pattern) . '$/';
                if (preg_match($regex, $value)) {
                    return $pattern;
                }
            } elseif ($value === $pattern) {
                return $pattern;
            }
        }
        return null;
    }

    private function logDecision(string $type, string $subject, array $decision): void
    {
        if (!$decision['allowed'] && ($this->policies['settings']['log_denials'] ?? true)) {
            Log::warning('Policy denial', [
                'type' => $type,
                'subject' => $subject,
                'reason' => $decision['reason'],
                'matched_rule' => $decision['matched_rule'],
            ]);
        }

        if ($decision['allowed'] && ($this->policies['settings']['log_approvals'] ?? false)) {
            Log::info('Policy approval', [
                'type' => $type,
                'subject' => $subject,
                'reason' => $decision['reason'],
                'matched_rule' => $decision['matched_rule'],
            ]);
        }
    }

    private function getDefaultPolicies(): array
    {
        return [
            'version' => '1.0-default',
            'settings' => ['default_action' => 'deny'],
            'tools' => ['allowed' => [], 'denied' => []],
            'shell' => ['commands' => ['allowed' => [], 'denied' => []]],
            'filesystem' => ['allowed_paths' => [], 'denied_paths' => []],
            'network' => ['allowed_domains' => [], 'denied_domains' => []],
            'risk_scoring' => ['base_weights' => [], 'threshold_actions' => []],
            'loaded_at' => now()->toIso8601String(),
        ];
    }
}
```

### 3. Register Service Provider

**File:** `app/Providers/SecurityServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Services\Security\PolicyRegistry;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PolicyRegistry::class);
    }

    public function boot(): void
    {
        // Validate policies on boot (non-production only)
        if (!app()->isProduction()) {
            $registry = app(PolicyRegistry::class);
            $errors = $registry->validate();
            
            if (!empty($errors)) {
                logger()->warning('Policy validation errors', $errors);
            }
        }
    }
}
```

Add to `bootstrap/providers.php`:
```php
App\Providers\SecurityServiceProvider::class,
```

### 4. Create Admin API Endpoint

**File:** `app/Http/Controllers/Admin/SecurityController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Security\PolicyRegistry;
use Illuminate\Http\JsonResponse;

class SecurityController extends Controller
{
    public function __construct(
        private PolicyRegistry $policyRegistry
    ) {}

    public function getPolicyInfo(): JsonResponse
    {
        return response()->json([
            'version' => $this->policyRegistry->getVersion(),
            'validation' => $this->policyRegistry->validate(),
        ]);
    }

    public function reloadPolicies(): JsonResponse
    {
        $this->policyRegistry->reload();
        
        return response()->json([
            'success' => true,
            'message' => 'Policies reloaded successfully',
            'version' => $this->policyRegistry->getVersion(),
        ]);
    }
}
```

Add routes to `routes/api.php`:
```php
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/security')->group(function () {
    Route::get('/policies', [SecurityController::class, 'getPolicyInfo']);
    Route::post('/policies/reload', [SecurityController::class, 'reloadPolicies']);
});
```

## Testing

### Unit Tests

**File:** `tests/Unit/Security/PolicyRegistryTest.php`

```php
<?php

namespace Tests\Unit\Security;

use App\Services\Security\PolicyRegistry;
use Tests\TestCase;

class PolicyRegistryTest extends TestCase
{
    private PolicyRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(PolicyRegistry::class);
    }

    public function test_tool_allowlist_matching()
    {
        $this->assertTrue($this->registry->isToolAllowed('shell'));
        $this->assertTrue($this->registry->isToolAllowed('fs.read'));
        $this->assertTrue($this->registry->isToolAllowed('mcp.server'));
        $this->assertFalse($this->registry->isToolAllowed('admin.delete'));
    }

    public function test_command_allowlist()
    {
        $this->assertTrue($this->registry->isCommandAllowed('ls -la'));
        $this->assertTrue($this->registry->isCommandAllowed('git status'));
        $this->assertFalse($this->registry->isCommandAllowed('rm -rf /'));
        $this->assertFalse($this->registry->isCommandAllowed('sudo apt-get'));
    }

    public function test_path_restrictions()
    {
        $this->assertTrue($this->registry->isPathAllowed('/workspace/file.txt'));
        $this->assertTrue($this->registry->isPathAllowed('/tmp/cache'));
        $this->assertFalse($this->registry->isPathAllowed('/etc/passwd'));
        $this->assertFalse($this->registry->isPathAllowed('~/.ssh/id_rsa'));
    }

    public function test_domain_allowlist()
    {
        $this->assertTrue($this->registry->isDomainAllowed('api.github.com'));
        $this->assertTrue($this->registry->isDomainAllowed('api.openai.com'));
        $this->assertFalse($this->registry->isDomainAllowed('evil.internal'));
        $this->assertFalse($this->registry->isDomainAllowed('localhost'));
    }

    public function test_wildcard_matching()
    {
        $this->assertTrue($this->registry->isToolAllowed('fs.read'));
        $this->assertTrue($this->registry->isToolAllowed('fs.write'));
        $this->assertTrue($this->registry->isDomainAllowed('cdn.github.com'));
    }

    public function test_risk_threshold_actions()
    {
        $this->assertEquals('auto_approve', $this->registry->getThresholdAction(10));
        $this->assertEquals('require_approval', $this->registry->getThresholdAction(60));
        $this->assertEquals('require_approval_with_justification', $this->registry->getThresholdAction(85));
    }

    public function test_policy_validation()
    {
        $errors = $this->registry->validate();
        $this->assertEmpty($errors, 'Policy validation should pass');
    }

    public function test_policy_reload()
    {
        $before = $this->registry->getVersion();
        $this->registry->reload();
        $after = $this->registry->getVersion();
        
        $this->assertNotEquals($before['loaded_at'], $after['loaded_at']);
    }
}
```

## Acceptance Criteria

- [ ] PolicyRegistry loads from YAML file
- [ ] All policy types supported (tools, commands, paths, domains)
- [ ] Wildcard matching works correctly
- [ ] Deny rules override allow rules
- [ ] Policy validation catches syntax errors
- [ ] Hot-reload API endpoint works
- [ ] Policy decisions are logged
- [ ] Performance: lookups < 5ms
- [ ] Unit tests pass with >= 80% coverage
- [ ] Integration with existing PermissionGate

## Estimated Effort
- Policy configuration design: 0.5 days
- PolicyRegistry implementation: 1 day
- Admin API endpoints: 0.25 days
- Testing: 0.25 days

**Total: 2 days**

## Dependencies
- None - can start immediately

## Next Steps
After completion:
- GUARD-002: Risk Scoring Engine (integrates with PolicyRegistry)
- GUARD-003: Dry-Run Mode (uses PolicyRegistry for pre-flight checks)
