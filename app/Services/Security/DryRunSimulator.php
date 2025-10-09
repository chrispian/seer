<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Dry-run simulator for security-critical operations.
 *
 * Simulates tool calls, commands, file operations, and network requests
 * without executing them. Provides:
 * - Policy evaluation (allowlist checks)
 * - Risk assessment (scoring and approval requirements)
 * - Change prediction (what would happen if executed)
 * - Parameter sanitization (redact sensitive values)
 *
 * Used for:
 * - Pre-execution validation and preview
 * - User approval workflows (show what will happen)
 * - Debugging and testing security policies
 * - Audit logging and compliance
 *
 * Example:
 *     $simulator = new DryRunSimulator($policyRegistry, $riskScorer);
 *     $result = $simulator->simulateCommand('rm -rf /tmp/cache');
 *     // Shows policy check, risk score, predicted changes without executing
 *
 * @see PolicyRegistry For policy evaluation
 * @see RiskScorer For risk assessment
 * @see ApprovalManager For approval workflow integration
 */
class DryRunSimulator
{
    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    /**
     * Simulate a tool call without executing it.
     *
     * Evaluates:
     * 1. Policy check (tool allowlist)
     * 2. Risk assessment (scoring and approval requirements)
     * 3. Change prediction (what files/resources would be affected)
     * 4. Execution decision (would auto-approve or require approval)
     *
     * @param string $toolId Tool identifier (e.g., 'fs.write', 'shell.exec')
     * @param array<string, mixed> $parameters Tool parameters (sanitized for sensitive values)
     * @param array<string, mixed> $context Simulation context (user info, etc.)
     * @return array{
     *     tool_id: string,
     *     parameters: array,
     *     would_execute: bool,
     *     policy_check: array|null,
     *     risk_assessment: array|null,
     *     predicted_changes: array[],
     *     warnings: string[],
     *     simulated_at: string
     * } Simulation result
     *
     * Example - Auto-approved:
     *     $result = $simulator->simulateToolCall('fs.read', ['path' => '/var/log/app.log']);
     *     // ['would_execute' => true, 'risk_assessment' => ['action' => 'auto_approve'], ...]
     *
     * Example - Requires approval:
     *     $result = $simulator->simulateToolCall('fs.delete', ['path' => '/etc/config']);
     *     // ['would_execute' => false, 'warnings' => ['REQUIRES APPROVAL: Risk score 85 (high)'], ...]
     */
    public function simulateToolCall(string $toolId, array $parameters, array $context = []): array
    {
        $simulation = [
            'tool_id' => $toolId,
            'parameters' => $this->sanitizeParameters($parameters),
            'would_execute' => false,
            'policy_check' => null,
            'risk_assessment' => null,
            'predicted_changes' => [],
            'warnings' => [],
            'simulated_at' => now()->toIso8601String(),
        ];

        // Check policy
        $policyDecision = $this->policyRegistry->isToolAllowed($toolId);
        $simulation['policy_check'] = $policyDecision;

        if (! $policyDecision['allowed']) {
            $simulation['would_execute'] = false;
            $simulation['warnings'][] = "BLOCKED: {$policyDecision['reason']}";

            return $simulation;
        }

        // Calculate risk
        $riskAssessment = $this->riskScorer->scoreToolCall($toolId, $parameters);
        $simulation['risk_assessment'] = $riskAssessment;

        // Predict what would happen
        $simulation['predicted_changes'] = $this->predictChanges($toolId, $parameters);

        // Determine if would execute
        $simulation['would_execute'] = $riskAssessment['action'] === 'auto_approve';

        if ($riskAssessment['requires_approval']) {
            $simulation['warnings'][] = "REQUIRES APPROVAL: Risk score {$riskAssessment['score']} ({$riskAssessment['level']})";
        }

        return $simulation;
    }

    /**
     * Simulate a shell command without executing.
     *
     * Evaluates command through full security pipeline and predicts
     * changes (file operations, package installs, etc.).
     *
     * @param string $command Shell command (e.g., 'rm -rf /tmp/cache', 'npm install')
     * @param array<string, mixed> $context Simulation context
     * @return array{
     *     command: string,
     *     would_execute: bool,
     *     policy_check: array|null,
     *     risk_assessment: array|null,
     *     predicted_changes: array[],
     *     warnings: string[],
     *     simulated_at: string
     * } Simulation result
     */
    public function simulateCommand(string $command, array $context = []): array
    {
        $simulation = [
            'command' => $command,
            'would_execute' => false,
            'policy_check' => null,
            'risk_assessment' => null,
            'predicted_changes' => [],
            'warnings' => [],
            'simulated_at' => now()->toIso8601String(),
        ];

        // Check policy
        $policyDecision = $this->policyRegistry->isCommandAllowed($command);
        $simulation['policy_check'] = $policyDecision;

        if (! $policyDecision['allowed']) {
            $simulation['would_execute'] = false;
            $simulation['warnings'][] = "BLOCKED: {$policyDecision['reason']}";

            return $simulation;
        }

        // Calculate risk
        $riskAssessment = $this->riskScorer->scoreCommand($command, $context);
        $simulation['risk_assessment'] = $riskAssessment;

        // Predict changes
        $simulation['predicted_changes'] = $this->predictCommandChanges($command, $context);

        // Would execute?
        $simulation['would_execute'] = $riskAssessment['action'] === 'auto_approve';

        if ($riskAssessment['requires_approval']) {
            $simulation['warnings'][] = "REQUIRES APPROVAL: Risk score {$riskAssessment['score']} ({$riskAssessment['level']})";
        }

        return $simulation;
    }

    /**
     * Simulate file operation without executing.
     *
     * Predicts changes based on operation type and file existence.
     *
     * @param string $path Filesystem path
     * @param string $operation Operation type ('read', 'write', 'delete')
     * @param array<string, mixed> $context Simulation context (size, etc.)
     * @return array{
     *     path: string,
     *     operation: string,
     *     would_execute: bool,
     *     policy_check: array|null,
     *     risk_assessment: array|null,
     *     predicted_changes: array[],
     *     warnings: string[],
     *     simulated_at: string
     * } Simulation result
     */
    public function simulateFileOperation(string $path, string $operation, array $context = []): array
    {
        $simulation = [
            'path' => $path,
            'operation' => $operation,
            'would_execute' => false,
            'policy_check' => null,
            'risk_assessment' => null,
            'predicted_changes' => [],
            'warnings' => [],
            'simulated_at' => now()->toIso8601String(),
        ];

        // Check policy
        $policyDecision = $this->policyRegistry->isPathAllowed($path, $operation);
        $simulation['policy_check'] = $policyDecision;

        if (! $policyDecision['allowed']) {
            $simulation['would_execute'] = false;
            $simulation['warnings'][] = "BLOCKED: {$policyDecision['reason']}";

            return $simulation;
        }

        // Calculate risk
        $riskAssessment = $this->riskScorer->scoreFileOperation($path, $operation, $context);
        $simulation['risk_assessment'] = $riskAssessment;

        // Predict changes
        $simulation['predicted_changes'] = $this->predictFileChanges($path, $operation, $context);

        // Would execute?
        $simulation['would_execute'] = $riskAssessment['action'] === 'auto_approve';

        if ($riskAssessment['requires_approval']) {
            $simulation['warnings'][] = "REQUIRES APPROVAL: Risk score {$riskAssessment['score']} ({$riskAssessment['level']})";
        }

        return $simulation;
    }

    /**
     * Simulate network operation without executing.
     *
     * Evaluates URL/domain policies and predicts request details.
     *
     * @param string $url Request URL
     * @param array<string, mixed> $context Simulation context (method, body, etc.)
     * @return array{
     *     url: string,
     *     domain: string,
     *     would_execute: bool,
     *     policy_check: array|null,
     *     risk_assessment: array|null,
     *     predicted_changes: array[],
     *     warnings: string[],
     *     simulated_at: string
     * } Simulation result
     */
    public function simulateNetworkOperation(string $url, array $context = []): array
    {
        $domain = parse_url($url, PHP_URL_HOST) ?? $url;

        $simulation = [
            'url' => $url,
            'domain' => $domain,
            'would_execute' => false,
            'policy_check' => null,
            'risk_assessment' => null,
            'predicted_changes' => [],
            'warnings' => [],
            'simulated_at' => now()->toIso8601String(),
        ];

        // Check policy
        $policyDecision = $this->policyRegistry->isDomainAllowed($domain);
        $simulation['policy_check'] = $policyDecision;

        if (! $policyDecision['allowed']) {
            $simulation['would_execute'] = false;
            $simulation['warnings'][] = "BLOCKED: {$policyDecision['reason']}";

            return $simulation;
        }

        // Calculate risk
        $riskAssessment = $this->riskScorer->scoreNetworkOperation($domain, $context);
        $simulation['risk_assessment'] = $riskAssessment;

        // Predict changes
        $simulation['predicted_changes'] = $this->predictNetworkChanges($url, $context);

        // Would execute?
        $simulation['would_execute'] = $riskAssessment['action'] === 'auto_approve';

        if ($riskAssessment['requires_approval']) {
            $simulation['warnings'][] = "REQUIRES APPROVAL: Risk score {$riskAssessment['score']} ({$riskAssessment['level']})";
        }

        return $simulation;
    }

    // ==================== Change Prediction Methods ====================

    /**
     * Predict changes from tool call based on tool ID patterns.
     *
     * Recognizes:
     * - fs.write: File write operations
     * - fs.delete: File delete operations
     * - *shell*: Shell command execution
     *
     * @param string $toolId Tool identifier
     * @param array<string, mixed> $parameters Tool parameters
     * @return array[] Predicted changes with type, target, description
     */
    private function predictChanges(string $toolId, array $parameters): array
    {
        $changes = [];

        if (str_starts_with($toolId, 'fs.write')) {
            $changes[] = [
                'type' => 'file_write',
                'target' => $parameters['path'] ?? 'unknown',
                'description' => 'Would create or modify file',
            ];
        }

        if (str_starts_with($toolId, 'fs.delete')) {
            $changes[] = [
                'type' => 'file_delete',
                'target' => $parameters['path'] ?? 'unknown',
                'description' => 'Would delete file',
            ];
        }

        if (str_contains($toolId, 'shell')) {
            $changes[] = [
                'type' => 'shell_execution',
                'description' => 'Would execute shell command',
            ];
        }

        return $changes;
    }

    /**
     * Predict changes from shell command based on binary and patterns.
     *
     * Detects:
     * - Known command effects (rm, mkdir, git, npm, composer)
     * - Output redirection (> file)
     *
     * @param string $command Shell command
     * @param array<string, mixed> $context Command context
     * @return array[] Predicted changes with type and description
     */
    private function predictCommandChanges(string $command, array $context): array
    {
        $changes = [];
        $baseCommand = explode(' ', trim($command))[0];

        $commandEffects = [
            'rm' => ['type' => 'file_delete', 'description' => 'Would delete files'],
            'mkdir' => ['type' => 'directory_create', 'description' => 'Would create directory'],
            'touch' => ['type' => 'file_create', 'description' => 'Would create/update file'],
            'git' => ['type' => 'vcs_operation', 'description' => 'Would modify version control'],
            'npm' => ['type' => 'package_operation', 'description' => 'Would modify packages'],
            'composer' => ['type' => 'package_operation', 'description' => 'Would modify packages'],
        ];

        if (isset($commandEffects[$baseCommand])) {
            $changes[] = $commandEffects[$baseCommand];
        }

        // Parse for output redirection
        if (preg_match('/>\s*([^\s]+)/', $command, $matches)) {
            $changes[] = [
                'type' => 'file_write',
                'target' => $matches[1],
                'description' => 'Would write to file via redirection',
            ];
        }

        return $changes;
    }

    /**
     * Predict changes from file operation based on operation type and file existence.
     *
     * Handles:
     * - write: Create new or modify existing file
     * - delete: Remove existing file
     * - read: Read file contents (no changes)
     *
     * @param string $path Filesystem path
     * @param string $operation Operation type
     * @param array<string, mixed> $context Operation context (size, etc.)
     * @return array[] Predicted changes with type, target, description, size info
     */
    private function predictFileChanges(string $path, string $operation, array $context): array
    {
        $changes = [];

        $exists = file_exists($path);

        switch ($operation) {
            case 'write':
                $changes[] = [
                    'type' => $exists ? 'file_modify' : 'file_create',
                    'target' => $path,
                    'description' => $exists ? 'Would modify existing file' : 'Would create new file',
                    'size_estimate' => $context['size'] ?? null,
                ];
                break;

            case 'delete':
                if ($exists) {
                    $changes[] = [
                        'type' => 'file_delete',
                        'target' => $path,
                        'description' => 'Would delete file',
                        'current_size' => @filesize($path),
                    ];
                }
                break;

            case 'read':
                $changes[] = [
                    'type' => 'file_read',
                    'target' => $path,
                    'description' => 'Would read file contents',
                    'exists' => $exists,
                ];
                break;
        }

        return $changes;
    }

    /**
     * Predict changes from network operation based on HTTP method and context.
     *
     * Detects:
     * - Network request details (URL, method)
     * - Data uploads (POST/PUT with body)
     *
     * @param string $url Request URL
     * @param array<string, mixed> $context Request context (method, has_body, body_size)
     * @return array[] Predicted changes with type and description
     */
    private function predictNetworkChanges(string $url, array $context): array
    {
        $changes = [];
        $method = strtoupper($context['method'] ?? 'GET');

        $changes[] = [
            'type' => 'network_request',
            'url' => $url,
            'method' => $method,
            'description' => "Would send {$method} request to {$url}",
        ];

        if (isset($context['has_body']) && $context['has_body']) {
            $changes[] = [
                'type' => 'data_upload',
                'description' => 'Would upload data to remote server',
                'size_estimate' => $context['body_size'] ?? null,
            ];
        }

        return $changes;
    }

    /**
     * Sanitize parameters for display by redacting sensitive values.
     *
     * Redacts keys: password, secret, token, api_key, private_key, apiKey
     *
     * @param array<string, mixed> $parameters Raw parameters
     * @return array<string, mixed> Sanitized parameters with ***REDACTED*** for sensitive values
     */
    private function sanitizeParameters(array $parameters): array
    {
        $sanitized = [];
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'private_key', 'apiKey'];

        foreach ($parameters as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $sanitized[$key] = '***REDACTED***';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
