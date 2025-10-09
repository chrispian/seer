<?php

namespace App\Services\Security;

use App\Models\SecurityPolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PolicyRegistry
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CACHE_KEY_PREFIX = 'security:policies:';

    /**
     * Check if a tool is allowed
     */
    public function isToolAllowed(string $toolId): array
    {
        return $this->evaluate('tool', null, $toolId);
    }

    /**
     * Check if a shell command is allowed
     */
    public function isCommandAllowed(string $command): array
    {
        // Extract base command (first word)
        $baseCommand = explode(' ', trim($command))[0];

        return $this->evaluate('command', 'shell', $baseCommand);
    }

    /**
     * Check if a filesystem path is allowed
     */
    public function isPathAllowed(string $path, string $operation = 'read'): array
    {
        // Normalize path
        $normalizedPath = $this->normalizePath($path);

        return $this->evaluate('path', 'filesystem', $normalizedPath);
    }

    /**
     * Check if a network domain is allowed
     */
    public function isDomainAllowed(string $domain): array
    {
        return $this->evaluate('domain', 'network', $domain);
    }

    /**
     * Get all policies of a specific type
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
     * Get all active policies
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
     * Clear policy cache (called when policies change)
     */
    public function clearCache(): void
    {
        SecurityPolicy::clearPolicyCache();
    }

    /**
     * Get risk weight for a pattern
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
     * Export policies to YAML format
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
     * Get policy statistics
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
     * Core evaluation logic
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
     * Check if subject matches pattern (with wildcard support)
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
     * Normalize filesystem path
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
     * Log policy decision
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
