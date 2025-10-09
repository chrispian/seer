<?php

namespace App\Services\Security\Guards;

use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;
use Illuminate\Support\Facades\Log;

class ShellGuard
{
    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    public function validateCommand(string $command, array $context = []): array
    {
        $validation = [
            'allowed' => false,
            'sanitized_command' => null,
            'policy_decision' => null,
            'risk_assessment' => null,
            'violations' => [],
            'warnings' => [],
        ];

        $policyDecision = $this->policyRegistry->isCommandAllowed($command);
        $validation['policy_decision'] = $policyDecision;

        if (!$policyDecision['allowed']) {
            $validation['violations'][] = $policyDecision['reason'];
            return $validation;
        }

        $risk = $this->riskScorer->scoreCommand($command, $context);
        $validation['risk_assessment'] = $risk;

        if ($risk['requires_approval']) {
            $validation['violations'][] = "Command requires approval (risk: {$risk['score']})";
            return $validation;
        }

        $injectionCheck = $this->detectInjectionAttempt($command);
        if (!$injectionCheck['safe']) {
            $validation['violations'][] = $injectionCheck['reason'];
            return $validation;
        }

        $parsed = $this->parseCommand($command);
        if (!$parsed['valid']) {
            $validation['violations'][] = $parsed['error'];
            return $validation;
        }

        $argValidation = $this->validateArguments($parsed['binary'], $parsed['arguments']);
        if (!$argValidation['valid']) {
            $validation['violations'] = array_merge($validation['violations'], $argValidation['errors']);
            return $validation;
        }

        $sanitized = $this->sanitizeCommand($parsed);
        $validation['sanitized_command'] = $sanitized;
        $validation['allowed'] = true;

        if (!empty($argValidation['warnings'])) {
            $validation['warnings'] = $argValidation['warnings'];
        }

        return $validation;
    }

    public function getResourceLimits(string $binary): array
    {
        return match($binary) {
            'npm', 'composer' => ['timeout' => 300, 'memory' => '1G'],
            'git' => ['timeout' => 120, 'memory' => '512M'],
            'php' => ['timeout' => 60, 'memory' => '256M'],
            default => ['timeout' => 30, 'memory' => '128M'],
        };
    }

    private function detectInjectionAttempt(string $command): array
    {
        $safePipeTargets = ['grep', 'awk', 'sed', 'sort', 'uniq', 'head', 'tail', 'wc'];
        
        if (preg_match('/\|\s*(\w+)/', $command, $matches)) {
            if (in_array($matches[1], $safePipeTargets)) {
                return ['safe' => true];
            }
        }

        $dangerousPatterns = [
            '/;/' => 'Semicolon command separator',
            '/&&/' => 'AND command chaining',
            '/\$\(/' => 'Command substitution',
        ];

        foreach ($dangerousPatterns as $pattern => $reason) {
            if (preg_match($pattern, $command)) {
                return ['safe' => false, 'reason' => $reason];
            }
        }

        return ['safe' => true];
    }

    private function parseCommand(string $command): array
    {
        $parts = preg_split('/\s+/', trim($command));
        return empty($parts) ? ['valid' => false, 'error' => 'Empty command'] : [
            'valid' => true,
            'binary' => $parts[0],
            'arguments' => array_slice($parts, 1),
        ];
    }

    private function validateArguments(string $binary, array $arguments): array
    {
        return match($binary) {
            'rm' => $this->validateRmArguments($arguments),
            'git' => $this->validateGitArguments($arguments),
            default => ['valid' => true],
        };
    }

    private function validateRmArguments(array $arguments): array
    {
        $hasRf = in_array('-rf', $arguments) || (in_array('-r', $arguments) && in_array('-f', $arguments));
        if ($hasRf) {
            return ['valid' => false, 'errors' => ['rm -rf is blocked']];
        }
        return ['valid' => true];
    }

    private function validateGitArguments(array $arguments): array
    {
        if (str_contains(implode(' ', $arguments), 'push --force')) {
            return ['valid' => false, 'errors' => ['git push --force is blocked']];
        }
        return ['valid' => true];
    }

    private function sanitizeCommand(array $parsed): string
    {
        return $parsed['binary'] . ' ' . implode(' ', $parsed['arguments']);
    }
}
