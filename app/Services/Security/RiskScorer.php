<?php

namespace App\Services\Security;

class RiskScorer
{
    /**
     * Risk scoring weights (0-100 scale)
     */
    private const BASE_WEIGHTS = [
        'read_operation' => 1,
        'write_operation' => 10,
        'delete_operation' => 25,
        'network_egress' => 15,
        'shell_execution' => 35,
        'privileged_operation' => 50,
        'system_modification' => 40,
        'data_exfiltration_risk' => 30,
    ];

    /**
     * Threshold actions (risk score ranges)
     */
    private const THRESHOLDS = [
        0 => 'auto_approve',           // 0-25: Low risk
        26 => 'require_approval',       // 26+: Require approval
        51 => 'require_approval',      // 51-75: High risk
        76 => 'require_approval_with_justification', // 76-100: Critical
    ];

    public function __construct(
        private PolicyRegistry $policyRegistry
    ) {}

    /**
     * Calculate risk score for a tool call
     */
    public function scoreToolCall(string $toolId, array $parameters = []): array
    {
        $score = 0;
        $factors = [];

        // Base tool risk from policy metadata
        $toolRisk = $this->policyRegistry->getRiskWeight('tool', $toolId);
        if ($toolRisk > 0) {
            $score += $toolRisk;
            $factors[] = "Tool base risk: +{$toolRisk}";
        }

        // Analyze tool type
        if (str_starts_with($toolId, 'shell')) {
            $score += self::BASE_WEIGHTS['shell_execution'];
            $factors[] = 'Shell execution: +'.self::BASE_WEIGHTS['shell_execution'];
        }

        if (str_contains($toolId, 'delete') || str_contains($toolId, 'remove')) {
            $score += self::BASE_WEIGHTS['delete_operation'];
            $factors[] = 'Delete operation: +'.self::BASE_WEIGHTS['delete_operation'];
        }

        if (str_contains($toolId, 'write') || str_contains($toolId, 'create')) {
            $score += self::BASE_WEIGHTS['write_operation'];
            $factors[] = 'Write operation: +'.self::BASE_WEIGHTS['write_operation'];
        }

        // Network operations
        if (str_contains($toolId, 'http') || str_contains($toolId, 'fetch') || str_contains($toolId, 'request')) {
            $score += self::BASE_WEIGHTS['network_egress'];
            $factors[] = 'Network egress: +'.self::BASE_WEIGHTS['network_egress'];
        }

        // Parameter analysis
        $paramRisk = $this->analyzeParameters($parameters);
        if ($paramRisk['score'] > 0) {
            $score += $paramRisk['score'];
            $factors = array_merge($factors, $paramRisk['factors']);
        }

        // Cap at 100
        $score = min($score, 100);

        return [
            'score' => $score,
            'level' => $this->getRiskLevel($score),
            'action' => $this->getThresholdAction($score),
            'factors' => $factors,
            'requires_approval' => $score >= 26,
        ];
    }

    /**
     * Calculate risk score for a shell command
     */
    public function scoreCommand(string $command, array $context = []): array
    {
        $score = 0;
        $factors = [];

        // Base command risk from policy
        $baseCommand = explode(' ', trim($command))[0];
        $commandRisk = $this->policyRegistry->getRiskWeight('command', $baseCommand);
        if ($commandRisk > 0) {
            $score += $commandRisk;
            $factors[] = "Command base risk: +{$commandRisk}";
        }

        // Shell execution baseline
        $score += self::BASE_WEIGHTS['shell_execution'];
        $factors[] = 'Shell execution: +'.self::BASE_WEIGHTS['shell_execution'];

        // Dangerous patterns
        $dangerousPatterns = [
            '/rm\s+-rf/i' => ['weight' => 40, 'reason' => 'Recursive force delete'],
            '/sudo/i' => ['weight' => 50, 'reason' => 'Privileged execution'],
            '/chmod\s+777/i' => ['weight' => 30, 'reason' => 'Insecure permissions'],
            '/wget|curl.*\|.*sh/i' => ['weight' => 45, 'reason' => 'Pipe to shell'],
            '/dd\s+if=/i' => ['weight' => 50, 'reason' => 'Disk manipulation'],
            '/mkfs/i' => ['weight' => 50, 'reason' => 'Filesystem creation'],
            '/>\s*\/dev\/sd[a-z]/i' => ['weight' => 50, 'reason' => 'Direct disk write'],
            '/mysql.*--password/i' => ['weight' => 30, 'reason' => 'Password in command'],
        ];

        foreach ($dangerousPatterns as $pattern => $risk) {
            if (preg_match($pattern, $command)) {
                $score += $risk['weight'];
                $factors[] = "{$risk['reason']}: +{$risk['weight']}";
            }
        }

        // Redirection and piping (mild risk)
        if (preg_match('/[|&;]/', $command)) {
            $score += 5;
            $factors[] = 'Command chaining: +5';
        }

        // Working outside allowed directories
        if (isset($context['workdir'])) {
            $workdir = $context['workdir'];
            $decision = $this->policyRegistry->isPathAllowed($workdir);
            if (! $decision['allowed']) {
                $score += 20;
                $factors[] = 'Working in restricted directory: +20';
            }
        }

        // Cap at 100
        $score = min($score, 100);

        return [
            'score' => $score,
            'level' => $this->getRiskLevel($score),
            'action' => $this->getThresholdAction($score),
            'factors' => $factors,
            'requires_approval' => $score >= 26,
        ];
    }

    /**
     * Calculate risk score for filesystem operation
     */
    public function scoreFileOperation(string $path, string $operation, array $context = []): array
    {
        $score = 0;
        $factors = [];

        // Check path policy
        $decision = $this->policyRegistry->isPathAllowed($path, $operation);
        if (! $decision['allowed']) {
            $score += 30;
            $factors[] = 'Restricted path access: +30';
        }

        // Operation type
        $operationWeights = [
            'read' => self::BASE_WEIGHTS['read_operation'],
            'write' => self::BASE_WEIGHTS['write_operation'],
            'delete' => self::BASE_WEIGHTS['delete_operation'],
            'execute' => self::BASE_WEIGHTS['shell_execution'],
        ];

        $opWeight = $operationWeights[$operation] ?? 0;
        if ($opWeight > 0) {
            $score += $opWeight;
            $factors[] = ucfirst($operation)." operation: +{$opWeight}";
        }

        // Sensitive file patterns (use preg_quote for paths)
        $sensitiveChecks = [
            '.ssh/' => ['weight' => 40, 'reason' => 'SSH keys'],
            '.env' => ['weight' => 35, 'reason' => 'Environment secrets'],
            '.aws/credentials' => ['weight' => 40, 'reason' => 'AWS credentials'],
            '/etc/passwd' => ['weight' => 45, 'reason' => 'System password file'],
            '/etc/shadow' => ['weight' => 50, 'reason' => 'System shadow file'],
            '.git/' => ['weight' => 15, 'reason' => 'Git repository data'],
        ];

        foreach ($sensitiveChecks as $pathPattern => $risk) {
            if (str_contains($path, $pathPattern)) {
                $score += $risk['weight'];
                $factors[] = "{$risk['reason']}: +{$risk['weight']}";
            }
        }

        // Bulk operations
        if (isset($context['file_count']) && $context['file_count'] > 10) {
            $bulkRisk = min(20, $context['file_count'] / 5);
            $score += $bulkRisk;
            $factors[] = "Bulk operation ({$context['file_count']} files): +{$bulkRisk}";
        }

        // Cap at 100
        $score = min($score, 100);

        return [
            'score' => $score,
            'level' => $this->getRiskLevel($score),
            'action' => $this->getThresholdAction($score),
            'factors' => $factors,
            'requires_approval' => $score >= 26,
        ];
    }

    /**
     * Calculate risk score for network operation
     */
    public function scoreNetworkOperation(string $domain, array $context = []): array
    {
        $score = 0;
        $factors = [];

        // Base network risk
        $score += self::BASE_WEIGHTS['network_egress'];
        $factors[] = 'Network egress: +'.self::BASE_WEIGHTS['network_egress'];

        // Check domain policy
        $decision = $this->policyRegistry->isDomainAllowed($domain);
        if (! $decision['allowed']) {
            $score += 25;
            $factors[] = 'Restricted domain: +25';
        }

        // Private IP detection (SSRF risk)
        if ($this->isPrivateIp($domain)) {
            $score += self::BASE_WEIGHTS['data_exfiltration_risk'];
            $factors[] = 'Private IP/SSRF risk: +'.self::BASE_WEIGHTS['data_exfiltration_risk'];
        }

        // Request method
        if (isset($context['method']) && in_array(strtoupper($context['method']), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $score += 5;
            $factors[] = 'Mutating HTTP method: +5';
        }

        // Data upload
        if (isset($context['has_body']) && $context['has_body']) {
            $score += 10;
            $factors[] = 'Data upload: +10';
        }

        // Sensitive headers
        if (isset($context['headers']) && is_array($context['headers'])) {
            if (isset($context['headers']['Authorization'])) {
                $score += 5;
                $factors[] = 'Contains auth token: +5';
            }
        }

        // Cap at 100
        $score = min($score, 100);

        return [
            'score' => $score,
            'level' => $this->getRiskLevel($score),
            'action' => $this->getThresholdAction($score),
            'factors' => $factors,
            'requires_approval' => $score >= 26,
        ];
    }

    /**
     * Get aggregate risk score for multiple operations
     */
    public function scoreOperationBatch(array $operations): array
    {
        $totalScore = 0;
        $allFactors = [];
        $highestLevel = 'low';

        foreach ($operations as $op) {
            $totalScore += $op['score'];
            $allFactors = array_merge($allFactors, $op['factors']);

            if ($this->levelValue($op['level']) > $this->levelValue($highestLevel)) {
                $highestLevel = $op['level'];
            }
        }

        // Average score
        $avgScore = count($operations) > 0 ? (int) ($totalScore / count($operations)) : 0;

        return [
            'score' => $avgScore,
            'level' => $this->getRiskLevel($avgScore),
            'action' => $this->getThresholdAction($avgScore),
            'factors' => $allFactors,
            'operation_count' => count($operations),
            'requires_approval' => $avgScore >= 26,
        ];
    }

    // ==================== Private Methods ====================

    /**
     * Analyze parameters for risk factors
     */
    private function analyzeParameters(array $parameters): array
    {
        $score = 0;
        $factors = [];

        // Check for passwords/secrets in params
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'private_key'];
        foreach ($sensitiveKeys as $key) {
            if (isset($parameters[$key])) {
                $score += 10;
                $factors[] = "Sensitive parameter ({$key}): +10";
            }
        }

        // Check for sudo/privileged flags
        if (isset($parameters['sudo']) || isset($parameters['privileged'])) {
            $score += self::BASE_WEIGHTS['privileged_operation'];
            $factors[] = 'Privileged mode: +'.self::BASE_WEIGHTS['privileged_operation'];
        }

        // Check for destructive flags
        if (isset($parameters['force']) || isset($parameters['recursive'])) {
            $score += 15;
            $factors[] = 'Force/recursive flag: +15';
        }

        return ['score' => $score, 'factors' => $factors];
    }

    /**
     * Get risk level from score
     */
    private function getRiskLevel(int $score): string
    {
        if ($score <= 25) {
            return 'low';
        }
        if ($score <= 50) {
            return 'medium';
        }
        if ($score <= 75) {
            return 'high';
        }

        return 'critical';
    }

    /**
     * Get threshold action for score
     */
    private function getThresholdAction(int $score): string
    {
        $action = 'auto_approve';

        foreach (self::THRESHOLDS as $threshold => $thresholdAction) {
            if ($score >= $threshold) {
                $action = $thresholdAction;
            }
        }

        return $action;
    }

    /**
     * Get numeric value for risk level (for comparison)
     */
    private function levelValue(string $level): int
    {
        return match ($level) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            default => 0,
        };
    }

    /**
     * Check if domain is a private IP address
     */
    private function isPrivateIp(string $domain): bool
    {
        // Check if it's localhost
        if (in_array($domain, ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }

        // Extract IP if domain is an IP address
        if (! filter_var($domain, FILTER_VALIDATE_IP)) {
            // Try to resolve domain to IP
            $ip = gethostbyname($domain);
            if ($ip === $domain) {
                return false; // Couldn't resolve
            }
        } else {
            $ip = $domain;
        }

        // Check private IP ranges
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
