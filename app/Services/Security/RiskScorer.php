<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Multi-dimensional risk scoring engine for security operations.
 *
 * Calculates risk scores (0-100) for tool calls, shell commands, filesystem
 * operations, and network requests. Scores determine whether operations
 * require user approval before execution.
 *
 * Risk Levels:
 * - 0-25 (low): Auto-approve - safe operations
 * - 26-50 (medium): Require approval - potentially risky
 * - 51-75 (high): Require approval - dangerous operations
 * - 76-100 (critical): Require approval with justification
 *
 * Scoring Factors:
 * - Base operation type (read: 1, write: 10, delete: 25, shell: 35)
 * - Dangerous patterns (sudo, rm -rf, pipe to shell)
 * - Sensitive paths (.ssh, .env, /etc/passwd)
 * - Private IPs (SSRF risk)
 * - Policy violations (restricted commands/paths/domains)
 *
 * @example Score a shell command
 * ```php
 * $scorer = app(RiskScorer::class);
 *
 * $result = $scorer->scoreCommand('rm -rf /tmp/*', [
 *     'user_id' => 1,
 *     'workdir' => '/tmp',
 * ]);
 *
 * // Result: [
 * //   'score' => 85,
 * //   'level' => 'critical',
 * //   'action' => 'require_approval',
 * //   'requires_approval' => true,
 * //   'factors' => ['Shell execution: +35', 'Recursive force delete: +40', ...]
 * // ]
 *
 * if ($result['requires_approval']) {
 *     // Show approval UI to user
 * }
 * ```
 * @example Score a file operation
 * ```php
 * $result = $scorer->scoreFileOperation('/etc/passwd', 'write', [
 *     'size' => 1024,
 * ]);
 *
 * echo "Risk: {$result['level']} ({$result['score']}/100)\n";
 * foreach ($result['factors'] as $factor) {
 *     echo "- {$factor}\n";
 * }
 * ```
 *
 * @see PolicyRegistry
 * @see ApprovalManager
 * @see RiskScorer::THRESHOLDS For risk threshold definitions
 */
class RiskScorer
{
    /**
     * Base risk weights for different operation types (0-100 scale).
     *
     * These weights are added to the risk score when specific operation
     * types are detected. Multiple weights may apply to a single operation.
     *
     * @var array<string, int>
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
     * Risk score threshold for low-risk operations (auto-approved).
     * Operations scoring 0-25 are considered safe and auto-approved.
     */
    public const LOW_RISK_THRESHOLD = 0;

    /**
     * Risk score threshold for requiring user approval.
     * Operations scoring 26+ require explicit user approval.
     */
    public const APPROVAL_THRESHOLD = 26;

    /**
     * Risk score threshold for high-risk operations.
     * Operations scoring 51-75 are considered high risk.
     */
    public const HIGH_RISK_THRESHOLD = 51;

    /**
     * Risk score threshold for critical operations.
     * Operations scoring 76-100 require approval with justification.
     */
    public const CRITICAL_RISK_THRESHOLD = 76;

    /**
     * Risk score thresholds that determine required approval actions.
     *
     * Thresholds are evaluated in order - the highest matching threshold
     * determines the action. Operations scoring >= APPROVAL_THRESHOLD require user approval.
     *
     * @var array<int, string>
     */
    private const THRESHOLDS = [
        self::LOW_RISK_THRESHOLD => 'auto_approve',
        self::APPROVAL_THRESHOLD => 'require_approval',
        self::HIGH_RISK_THRESHOLD => 'require_approval',
        self::CRITICAL_RISK_THRESHOLD => 'require_approval_with_justification',
    ];

    /**
     * Create a new risk scorer instance.
     *
     * @param  PolicyRegistry  $policyRegistry  Policy registry for checking allowlists and risk weights
     */
    public function __construct(
        private PolicyRegistry $policyRegistry
    ) {}

    /**
     * Calculate risk score for a tool call.
     *
     * Evaluates tool ID and parameters to determine risk level. Tool IDs
     * are analyzed for dangerous patterns (shell, delete, write, network)
     * and parameters are checked for sensitive data.
     *
     * @param  string  $toolId  The tool identifier (e.g., 'fs.write', 'shell.exec', 'http.fetch')
     * @param  array<string, mixed>  $parameters  Optional tool parameters to analyze for risk
     * @return array{
     *     score: int,
     *     level: string,
     *     action: string,
     *     factors: array<int, string>,
     *     requires_approval: bool
     * } Risk assessment with score (0-100), level, and contributing factors
     *
     * @example
     * ```php
     * $result = $scorer->scoreToolCall('shell.exec', [
     *     'command' => 'git status',
     *     'workdir' => '/workspace',
     * ]);
     * // Returns: ['score' => 35, 'level' => 'medium', 'requires_approval' => true, ...]
     * ```
     *
     * @see RiskScorer::analyzeParameters() For parameter risk analysis
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
            'requires_approval' => $score >= self::APPROVAL_THRESHOLD,
        ];
    }

    /**
     * Calculate risk score for a shell command.
     *
     * Analyzes command string for dangerous patterns including:
     * - Destructive operations (rm -rf, dd, mkfs)
     * - Privilege escalation (sudo)
     * - Insecure patterns (chmod 777, pipe to shell)
     * - Command chaining (|, &, ;)
     * - Restricted working directories
     *
     * @param  string  $command  The full shell command to score (e.g., 'git clone https://...')
     * @param  array<string, mixed>  $context  Optional context with workdir, user_id, etc.
     * @return array{
     *     score: int,
     *     level: string,
     *     action: string,
     *     factors: array<int, string>,
     *     requires_approval: bool
     * } Risk assessment with detailed factor breakdown
     *
     * @example Safe command
     * ```php
     * $result = $scorer->scoreCommand('ls -la', ['workdir' => '/workspace']);
     * // Returns: ['score' => 35, 'level' => 'medium', 'requires_approval' => true]
     * ```
     * @example Dangerous command
     * ```php
     * $result = $scorer->scoreCommand('sudo rm -rf /', ['workdir' => '/']);
     * // Returns: ['score' => 100, 'level' => 'critical', 'factors' => [
     * //   'Shell execution: +35', 'Privileged execution: +50', 'Recursive force delete: +40'
     * // ]]
     * ```
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
            'requires_approval' => $score >= self::APPROVAL_THRESHOLD,
        ];
    }

    /**
     * Calculate risk score for filesystem operation.
     *
     * Evaluates file path and operation type for risk factors including:
     * - Sensitive file patterns (.ssh, .env, .aws, /etc/passwd)
     * - Operation type (read: 1, write: 10, delete: 25, execute: 35)
     * - Policy restrictions (denied paths)
     * - Bulk operations (multiple files)
     *
     * @param  string  $path  The filesystem path being accessed
     * @param  string  $operation  The operation type ('read'|'write'|'delete'|'execute')
     * @param  array<string, mixed>  $context  Optional context with file_count, size, etc.
     * @return array{
     *     score: int,
     *     level: string,
     *     action: string,
     *     factors: array<int, string>,
     *     requires_approval: bool
     * } Risk assessment for the file operation
     *
     * @example Safe read operation
     * ```php
     * $result = $scorer->scoreFileOperation('/workspace/data.json', 'read');
     * // Returns: ['score' => 1, 'level' => 'low', 'requires_approval' => false]
     * ```
     * @example Dangerous write operation
     * ```php
     * $result = $scorer->scoreFileOperation('/etc/passwd', 'write');
     * // Returns: ['score' => 55, 'level' => 'high', 'factors' => [
     * //   'Write operation: +10', 'System password file: +45'
     * // ]]
     * ```
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
            'requires_approval' => $score >= self::APPROVAL_THRESHOLD,
        ];
    }

    /**
     * Calculate risk score for network operation.
     *
     * Evaluates network requests for security risks including:
     * - Private IPs (SSRF vulnerability risk)
     * - Restricted domains (policy violations)
     * - Mutating HTTP methods (POST, PUT, DELETE)
     * - Data upload (request body present)
     * - Authentication tokens in headers
     *
     * @param  string  $domain  The target domain or IP address
     * @param  array<string, mixed>  $context  Optional context with method, has_body, headers
     * @return array{
     *     score: int,
     *     level: string,
     *     action: string,
     *     factors: array<int, string>,
     *     requires_approval: bool
     * } Risk assessment for the network operation
     *
     * @example Safe GET request
     * ```php
     * $result = $scorer->scoreNetworkOperation('api.github.com', [
     *     'method' => 'GET',
     * ]);
     * // Returns: ['score' => 15, 'level' => 'low', 'requires_approval' => false]
     * ```
     * @example SSRF risk with private IP
     * ```php
     * $result = $scorer->scoreNetworkOperation('192.168.1.1', [
     *     'method' => 'POST',
     *     'has_body' => true,
     * ]);
     * // Returns: ['score' => 60, 'level' => 'high', 'factors' => [
     * //   'Network egress: +15', 'Private IP/SSRF risk: +30',
     * //   'Mutating HTTP method: +5', 'Data upload: +10'
     * // ]]
     * ```
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
            'requires_approval' => $score >= self::APPROVAL_THRESHOLD,
        ];
    }

    /**
     * Calculate aggregate risk score for multiple operations.
     *
     * Averages risk scores across multiple operations and returns the
     * combined assessment. Useful for evaluating complex multi-step
     * workflows or batch operations.
     *
     * @param  array<int, array{score: int, level: string, factors: array}>  $operations  Array of scored operations
     * @return array{
     *     score: int,
     *     level: string,
     *     action: string,
     *     factors: array<int, string>,
     *     operation_count: int,
     *     requires_approval: bool
     * } Aggregate risk assessment with all contributing factors
     *
     * @example
     * ```php
     * $op1 = $scorer->scoreCommand('git clone https://...');
     * $op2 = $scorer->scoreFileOperation('/workspace/file.txt', 'write');
     * $op3 = $scorer->scoreNetworkOperation('api.github.com');
     *
     * $aggregate = $scorer->scoreOperationBatch([$op1, $op2, $op3]);
     * // Returns average score with all factors combined
     * ```
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
            'requires_approval' => $avgScore >= self::APPROVAL_THRESHOLD,
        ];
    }

    // ==================== Private Methods ====================

    /**
     * Analyze tool parameters for security risk factors.
     *
     * Checks parameters for:
     * - Sensitive keys (password, secret, token, api_key, private_key)
     * - Privilege escalation flags (sudo, privileged)
     * - Destructive flags (force, recursive)
     *
     * @param  array<string, mixed>  $parameters  Tool parameters to analyze
     * @return array{score: int, factors: array<int, string>} Risk score and contributing factors
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
     * Convert numeric risk score to human-readable level.
     *
     * Risk levels:
     * - 0-25: low
     * - 26-50: medium
     * - 51-75: high
     * - 76-100: critical
     *
     * @param  int  $score  The risk score (0-100)
     * @return string The risk level ('low'|'medium'|'high'|'critical')
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
     * Determine required action based on risk score threshold.
     *
     * Evaluates score against THRESHOLDS constant to determine if
     * approval is required and what type.
     *
     * @param  int  $score  The risk score (0-100)
     * @return string The threshold action ('auto_approve'|'require_approval'|'require_approval_with_justification')
     *
     * @see RiskScorer::THRESHOLDS
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
     * Convert risk level to numeric value for comparison.
     *
     * Used by scoreOperationBatch() to determine the highest risk level
     * across multiple operations.
     *
     * @param  string  $level  The risk level ('low'|'medium'|'high'|'critical')
     * @return int Numeric value (0-4) for comparison
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
     * Check if domain is a private/internal IP address (SSRF risk).
     *
     * Detects:
     * - Localhost addresses (127.0.0.1, ::1, localhost)
     * - Private IP ranges (10.x, 172.16-31.x, 192.168.x)
     * - Reserved IP ranges (link-local, etc.)
     *
     * This helps prevent Server-Side Request Forgery (SSRF) attacks
     * by flagging requests to internal infrastructure.
     *
     * @param  string  $domain  The domain or IP address to check
     * @return bool True if domain resolves to a private/reserved IP
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
