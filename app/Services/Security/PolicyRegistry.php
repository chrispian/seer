<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityPolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Central registry for security policy evaluation and caching.
 * 
 * This service provides deny-by-default security policy enforcement across
 * all tool executions, shell commands, filesystem operations, and network
 * requests. Policies are stored in the database and cached for performance.
 * 
 * Key Features:
 * - Database-driven policy storage (hot-reloadable)
 * - 1-hour cache with automatic invalidation on policy changes
 * - Pattern matching with wildcard support (*, glob patterns)
 * - Priority-based policy evaluation
 * - Risk weight metadata for integration with RiskScorer
 * - YAML export capability for backup/restore
 * 
 * @example Basic command check
 * ```php
 * $registry = app(PolicyRegistry::class);
 * 
 * $result = $registry->isCommandAllowed('git status');
 * if ($result['allowed']) {
 *     echo "Command allowed via rule: {$result['matched_rule']}";
 * } else {
 *     throw new SecurityException($result['reason']);
 * }
 * ```
 * 
 * @example Check filesystem path
 * ```php
 * $result = $registry->isPathAllowed('/tmp/test.txt', 'write');
 * if (!$result['allowed']) {
 *     Log::warning('Path access denied', $result);
 * }
 * ```
 * 
 * @see SecurityPolicy
 * @see RiskScorer
 * @see ShellGuard
 * @package App\Services\Security
 */
class PolicyRegistry
{
    /**
     * Cache time-to-live in seconds.
     * 
     * Policies are cached for 1 hour to minimize database queries while
     * still allowing reasonable update frequency for security changes.
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix for all policy-related cache entries.
     * 
     * Format: security:policies:{suffix}
     * Examples: security:policies:all, security:policies:type:command
     */
    private const CACHE_KEY_PREFIX = 'security:policies:';

    /**
     * Check if a tool is allowed to execute.
     * 
     * Evaluates tool ID against active policies to determine if execution
     * should be permitted. Tool IDs typically follow patterns like 'fs.read',
     * 'http.fetch', 'exec.shell', etc.
     * 
     * @param string $toolId The tool identifier (e.g., 'fs.read', 'http.fetch')
     * 
     * @return array{
     *     allowed: bool,
     *     reason: string,
     *     matched_rule: ?string,
     *     priority: int,
     *     policy_id: ?int,
     *     risk_weight: int
     * } Policy evaluation result with matched rule details
     * 
     * @example
     * ```php
     * $result = $registry->isToolAllowed('fs.write');
     * // ['allowed' => true, 'matched_rule' => 'fs.*', ...]
     * ```
     */
    public function isToolAllowed(string $toolId): array
    {
        return $this->evaluate('tool', null, $toolId);
    }

    /**
     * Check if a shell command is allowed to execute.
     * 
     * Extracts the base command (first word) and evaluates it against active
     * command policies. Arguments are ignored during policy evaluation to
     * allow flexible matching.
     * 
     * @param string $command The full command string (e.g., 'git status', 'rm -rf /tmp')
     * 
     * @return array{
     *     allowed: bool,
     *     reason: string,
     *     matched_rule: ?string,
     *     priority: int,
     *     policy_id: ?int,
     *     risk_weight: int
     * } Policy evaluation result
     * 
     * @example
     * ```php
     * $result = $registry->isCommandAllowed('git push origin main');
     * // Base command 'git' is checked, returns ['allowed' => true, ...]
     * ```
     * 
     * @example Denied command
     * ```php
     * $result = $registry->isCommandAllowed('sudo rm -rf /');
     * // Returns ['allowed' => false, 'reason' => 'Matched deny rule', ...]
     * ```
     */
    public function isCommandAllowed(string $command): array
    {
        // Extract base command (first word)
        $baseCommand = explode(' ', trim($command))[0];

        return $this->evaluate('command', 'shell', $baseCommand);
    }

    /**
     * Check if a filesystem path is allowed for the specified operation.
     * 
     * Normalizes the path (expands ~, resolves symlinks) before evaluating
     * against filesystem policies. Operation parameter is provided for context
     * but not currently used in policy matching.
     * 
     * @param string $path The filesystem path to check (e.g., '/tmp/file.txt', '~/documents')
     * @param string $operation The operation type ('read'|'write'|'delete')
     * 
     * @return array{
     *     allowed: bool,
     *     reason: string,
     *     matched_rule: ?string,
     *     priority: int,
     *     policy_id: ?int,
     *     risk_weight: int
     * } Policy evaluation result
     * 
     * @example
     * ```php
     * $result = $registry->isPathAllowed('/workspace/data.json', 'write');
     * // Returns ['allowed' => true, 'matched_rule' => '/workspace/*', ...]
     * ```
     */
    public function isPathAllowed(string $path, string $operation = 'read'): array
    {
        // Normalize path
        $normalizedPath = $this->normalizePath($path);

        return $this->evaluate('path', 'filesystem', $normalizedPath);
    }

    /**
     * Check if a network domain is allowed for outbound connections.
     * 
     * Evaluates domain against network policies to determine if HTTP requests,
     * API calls, or other network operations should be permitted.
     * 
     * @param string $domain The domain to check (e.g., 'api.github.com', 'localhost')
     * 
     * @return array{
     *     allowed: bool,
     *     reason: string,
     *     matched_rule: ?string,
     *     priority: int,
     *     policy_id: ?int,
     *     risk_weight: int
     * } Policy evaluation result
     * 
     * @example
     * ```php
     * $result = $registry->isDomainAllowed('api.github.com');
     * // Returns ['allowed' => true, 'matched_rule' => '*.github.com', ...]
     * ```
     */
    public function isDomainAllowed(string $domain): array
    {
        return $this->evaluate('domain', 'network', $domain);
    }

    /**
     * Get all active policies of a specific type.
     * 
     * Results are cached for 1 hour to minimize database queries.
     * Cache is automatically invalidated when policies are created, updated, or deleted.
     * 
     * @param string $type The policy type ('command'|'path'|'tool'|'domain')
     * 
     * @return \Illuminate\Support\Collection<int, SecurityPolicy> Collection of policies ordered by priority
     * 
     * @example
     * ```php
     * $commandPolicies = $registry->getPoliciesByType('command');
     * foreach ($commandPolicies as $policy) {
     *     echo "{$policy->pattern}: {$policy->action}\n";
     * }
     * ```
     */
    public function getPoliciesByType(string $type): \Illuminate\Support\Collection
    {
        $cacheKey = self::CACHE_KEY_PREFIX."type:{$type}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            return SecurityPolicy::active()
                ->byType($type)
                ->ordered()
                ->get();
        });
    }

    /**
     * Get all active policies across all types.
     * 
     * Returns policies from all types (command, path, tool, domain) in a single
     * collection, ordered by priority. Useful for exporting or viewing all policies.
     * 
     * @return \Illuminate\Support\Collection<int, SecurityPolicy> Collection of all active policies
     * 
     * @example
     * ```php
     * $allPolicies = $registry->getAllPolicies();
     * echo "Total active policies: " . $allPolicies->count();
     * ```
     */
    public function getAllPolicies(): \Illuminate\Support\Collection
    {
        $cacheKey = self::CACHE_KEY_PREFIX.'all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return SecurityPolicy::active()
                ->ordered()
                ->get();
        });
    }

    /**
     * Clear all policy-related caches.
     * 
     * Called automatically when policies are created, updated, or deleted via
     * the SecurityPolicy model. Forces fresh policy lookups on next evaluation.
     * 
     * @return void
     * 
     * @see SecurityPolicy::clearPolicyCache()
     */
    public function clearCache(): void
    {
        SecurityPolicy::clearPolicyCache();
    }

    /**
     * Get the risk weight for a specific pattern.
     * 
     * Risk weights are stored in policy metadata and used by RiskScorer to
     * calculate cumulative risk scores. Returns 0 if no matching policy found.
     * 
     * @param string $type The policy type ('command'|'path'|'tool'|'domain')
     * @param string $pattern The pattern to look up (e.g., 'rm', '/etc/*')
     * 
     * @return int The risk weight (0-100), or 0 if no match found
     * 
     * @example
     * ```php
     * $weight = $registry->getRiskWeight('command', 'rm');
     * // Returns 25 if 'rm' has a policy with risk_weight: 25 in metadata
     * ```
     * 
     * @see RiskScorer
     */
    public function getRiskWeight(string $type, string $pattern): int
    {
        $policies = $this->getPoliciesByType($type);

        foreach ($policies as $policy) {
            if ($this->matchesPattern($pattern, $policy->pattern)) {
                return $policy->getRiskWeight();
            }
        }

        return 0;
    }

    /**
     * Export all active policies to YAML format.
     * 
     * Generates a YAML document containing all policies grouped by type,
     * suitable for backup, documentation, or importing into other systems.
     * 
     * @return string YAML-formatted policy export
     * 
     * @throws \RuntimeException If yaml extension is not loaded
     * 
     * @example
     * ```php
     * $yaml = $registry->exportToYaml();
     * file_put_contents('policies-backup.yaml', $yaml);
     * ```
     */
    public function exportToYaml(): string
    {
        $policies = $this->getAllPolicies();

        $export = [
            'version' => '1.0',
            'updated_at' => now()->toIso8601String(),
            'policies' => [],
        ];

        foreach ($policies->groupBy('policy_type') as $type => $typePolicies) {
            $export['policies'][$type] = $typePolicies->map(function ($policy) {
                return [
                    'pattern' => $policy->pattern,
                    'action' => $policy->action,
                    'priority' => $policy->priority,
                    'category' => $policy->category,
                    'description' => $policy->description,
                    'metadata' => $policy->metadata,
                ];
            })->toArray();
        }

        return yaml_emit($export);
    }

    /**
     * Get statistics about currently active policies.
     * 
     * Provides counts by type, action, and overall totals for monitoring
     * and administrative dashboards.
     * 
     * @return array{
     *     total: int,
     *     by_type: array<string, int>,
     *     by_action: array<string, int>,
     *     active: int,
     *     cached_at: string
     * } Statistics array with counts and timestamp
     * 
     * @example
     * ```php
     * $stats = $registry->getStats();
     * // [
     * //   'total' => 42,
     * //   'by_type' => ['command' => 20, 'path' => 15, ...],
     * //   'by_action' => ['allow' => 35, 'deny' => 7],
     * //   'active' => 42,
     * //   'cached_at' => '2025-10-09T12:00:00Z'
     * // ]
     * ```
     */
    public function getStats(): array
    {
        $policies = $this->getAllPolicies();

        return [
            'total' => $policies->count(),
            'by_type' => $policies->groupBy('policy_type')->map->count()->toArray(),
            'by_action' => $policies->groupBy('action')->map->count()->toArray(),
            'active' => $policies->where('is_active', true)->count(),
            'cached_at' => now()->toIso8601String(),
        ];
    }

    // ==================== Private Methods ====================

    /**
     * Core policy evaluation logic.
     * 
     * Evaluates subject against policies of the specified type and category,
     * returning the first matching rule in priority order. Implements deny-by-default
     * security model - if no policy matches, access is denied.
     * 
     * @param string $type The policy type ('command'|'path'|'tool'|'domain')
     * @param string|null $category Optional category filter ('shell'|'filesystem'|'network')
     * @param string $subject The subject to evaluate (command, path, tool ID, or domain)
     * 
     * @return array{
     *     allowed: bool,
     *     reason: string,
     *     matched_rule: ?string,
     *     priority: int,
     *     policy_id: ?int,
     *     risk_weight: int
     * } Evaluation result with matched policy details
     */
    private function evaluate(string $type, ?string $category, string $subject): array
    {
        $policies = $this->getPoliciesByType($type);

        if ($category) {
            $policies = $policies->where('category', $category);
        }

        // Check policies in priority order (lower number = higher priority)
        foreach ($policies as $policy) {
            if ($this->matchesPattern($subject, $policy->pattern)) {
                $decision = [
                    'allowed' => $policy->action === 'allow',
                    'reason' => $policy->action === 'allow' ? 'Matched allow rule' : 'Matched deny rule',
                    'matched_rule' => $policy->pattern,
                    'priority' => $policy->priority,
                    'policy_id' => $policy->id,
                    'risk_weight' => $policy->getRiskWeight(),
                ];

                $this->logDecision($type, $subject, $decision);

                return $decision;
            }
        }

        // No match - default deny
        $decision = [
            'allowed' => false,
            'reason' => 'No matching policy (default deny)',
            'matched_rule' => null,
            'priority' => 999,
            'policy_id' => null,
            'risk_weight' => 0,
        ];

        $this->logDecision($type, $subject, $decision);

        return $decision;
    }

    /**
     * Check if subject matches pattern with wildcard support.
     * 
     * Supports multiple matching strategies:
     * - Exact match: 'git' matches 'git'
     * - Path prefix: '/workspace/*' matches '/workspace/file.txt'
     * - Wildcards: '*.github.com' matches 'api.github.com'
     * - Glob patterns: 'fs.*' matches 'fs.read', 'fs.write', etc.
     * 
     * @param string $subject The subject to test (e.g., 'api.github.com', '/tmp/file.txt')
     * @param string $pattern The pattern to match against (may include wildcards)
     * 
     * @return bool True if subject matches pattern, false otherwise
     */
    private function matchesPattern(string $subject, string $pattern): bool
    {
        // Exact match
        if ($subject === $pattern) {
            return true;
        }

        // Path prefix matching (e.g., "/workspace/*" matches "/workspace/file.txt")
        if (str_ends_with($pattern, '/*')) {
            $prefix = rtrim($pattern, '/*');
            if (str_starts_with($subject, $prefix)) {
                return true;
            }
        }

        // Wildcard matching for domain/tool patterns (e.g., "*.github.com", "fs.*")
        if (str_contains($pattern, '*')) {
            // Escape special regex characters, then replace escaped * with .*
            $escaped = preg_quote($pattern, '/');
            $regex = '/^'.str_replace('\\*', '.*', $escaped).'$/i';
            try {
                if (preg_match($regex, $subject)) {
                    return true;
                }
            } catch (\Exception $e) {
                Log::error('Pattern matching failed', [
                    'pattern' => $pattern,
                    'subject' => $subject,
                    'regex' => $regex,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return false;
    }

    /**
     * Normalize filesystem path for consistent policy evaluation.
     * 
     * Performs the following normalization:
     * - Expands ~ to home directory
     * - Resolves symlinks via realpath() if file exists
     * - Returns original path if file doesn't exist yet
     * 
     * @param string $path The raw filesystem path (may include ~ or relative paths)
     * 
     * @return string Normalized absolute path
     */
    private function normalizePath(string $path): string
    {
        // Expand ~ to home directory
        if (str_starts_with($path, '~')) {
            $path = str_replace('~', $_SERVER['HOME'] ?? '/home', $path);
        }

        // Resolve realpath if file exists
        $realpath = realpath($path);
        if ($realpath !== false) {
            return $realpath;
        }

        // Otherwise normalize the path string
        return $path;
    }

    /**
     * Log policy evaluation decision for audit and debugging.
     * 
     * Denied requests are logged as warnings for security monitoring.
     * Approved requests are logged as debug entries to avoid log noise.
     * 
     * @param string $type The policy type that was evaluated
     * @param string $subject The subject that was evaluated
     * @param array $decision The evaluation result from evaluate()
     * 
     * @return void
     */
    private function logDecision(string $type, string $subject, array $decision): void
    {
        if (! $decision['allowed']) {
            Log::warning('Security policy denial', [
                'type' => $type,
                'subject' => $subject,
                'reason' => $decision['reason'],
                'matched_rule' => $decision['matched_rule'],
                'policy_id' => $decision['policy_id'],
            ]);
        } else {
            Log::debug('Security policy approval', [
                'type' => $type,
                'subject' => $subject,
                'matched_rule' => $decision['matched_rule'],
            ]);
        }
    }
}
