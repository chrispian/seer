<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

class DryRunSimulator
{
    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    /**
     * Simulate a tool call without executing it
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

        if (!$policyDecision['allowed']) {
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
     * Simulate a shell command without executing
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

        if (!$policyDecision['allowed']) {
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
     * Simulate file operation
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

        if (!$policyDecision['allowed']) {
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
     * Simulate network operation
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

        if (!$policyDecision['allowed']) {
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
     * Predict changes from tool call
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
     * Predict changes from shell command
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
     * Predict changes from file operation
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
     * Predict changes from network operation
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
     * Sanitize parameters for display (remove sensitive values)
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
