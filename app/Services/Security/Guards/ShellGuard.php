<?php

declare(strict_types=1);

namespace App\Services\Security\Guards;

use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;

/**
 * Shell command security validation and sanitization guard.
 *
 * Validates shell commands through multiple security layers:
 * - Policy enforcement (command allowlist checking)
 * - Risk assessment (command risk scoring)
 * - Injection detection (command chaining, substitution)
 * - Argument validation (binary-specific rule checking)
 * - Command sanitization (safe execution formatting)
 *
 * Example:
 *     $guard = new ShellGuard($policyRegistry, $riskScorer);
 *     $result = $guard->validateCommand('git status', ['user_id' => 1]);
 *     if ($result['allowed']) {
 *         exec($result['sanitized_command']);
 *     }
 *
 * @see PolicyRegistry For command allowlist policies
 * @see RiskScorer For command risk assessment
 */
class ShellGuard
{
    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    /**
     * Validate a shell command through multiple security layers.
     *
     * Validation pipeline:
     * 1. Policy check (if not pre-approved)
     * 2. Risk assessment (may require approval)
     * 3. Injection detection (command chaining, substitution)
     * 4. Command parsing (binary + arguments extraction)
     * 5. Argument validation (binary-specific rules)
     * 6. Command sanitization (safe execution format)
     *
     * @param string $command Raw shell command to validate (e.g., 'git status', 'npm install')
     * @param array<string, mixed> $context Validation context:
     *        - 'approved' (bool): Skip policy/risk checks if user pre-approved
     *        - 'user_id' (int): User ID for risk scoring
     *        - Additional risk scoring context
     * @return array{
     *     allowed: bool,
     *     sanitized_command: string|null,
     *     policy_decision: array|null,
     *     risk_assessment: array|null,
     *     violations: string[],
     *     warnings: string[]
     * } Validation result
     *
     * Example - Allowed command:
     *     $result = $guard->validateCommand('git status');
     *     // ['allowed' => true, 'sanitized_command' => 'git status', ...]
     *
     * Example - Blocked command:
     *     $result = $guard->validateCommand('rm -rf /');
     *     // ['allowed' => false, 'violations' => ['rm -rf is blocked'], ...]
     *
     * Example - Pre-approved:
     *     $result = $guard->validateCommand('npm install', ['approved' => true]);
     *     // Skips policy and risk checks
     */
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

        // Skip approval check if already approved (via context flag)
        $alreadyApproved = $context['approved'] ?? false;

        // If already approved, skip policy check (user explicitly approved)
        if (! $alreadyApproved) {
            $policyDecision = $this->policyRegistry->isCommandAllowed($command);
            $validation['policy_decision'] = $policyDecision;

            if (! $policyDecision['allowed']) {
                $validation['violations'][] = $policyDecision['reason'];

                return $validation;
            }
        }

        $risk = $this->riskScorer->scoreCommand($command, $context);
        $validation['risk_assessment'] = $risk;

        if ($risk['requires_approval'] && ! $alreadyApproved) {
            $validation['violations'][] = "Command requires approval (risk: {$risk['score']})";

            return $validation;
        }

        $injectionCheck = $this->detectInjectionAttempt($command);
        if (! $injectionCheck['safe']) {
            $validation['violations'][] = $injectionCheck['reason'];

            return $validation;
        }

        $parsed = $this->parseCommand($command);
        if (! $parsed['valid']) {
            $validation['violations'][] = $parsed['error'];

            return $validation;
        }

        $argValidation = $this->validateArguments($parsed['binary'], $parsed['arguments']);
        if (! $argValidation['valid']) {
            $validation['violations'] = array_merge($validation['violations'], $argValidation['errors']);

            return $validation;
        }

        $sanitized = $this->sanitizeCommand($parsed);
        $validation['sanitized_command'] = $sanitized;
        $validation['allowed'] = true;

        if (! empty($argValidation['warnings'])) {
            $validation['warnings'] = $argValidation['warnings'];
        }

        return $validation;
    }

    /**
     * Get resource limits for command execution based on binary type.
     *
     * Returns timeout and memory limits appropriate for the command:
     * - npm/composer: 300s, 1GB (package management needs time/memory)
     * - git: 120s, 512MB (repository operations can be large)
     * - php: 60s, 256MB (script execution)
     * - default: 30s, 128MB (conservative limits)
     *
     * @param string $binary Binary name (e.g., 'npm', 'git', 'php')
     * @return array{timeout: int, memory: string} Resource limits
     *
     * Example:
     *     $limits = $guard->getResourceLimits('npm');
     *     // ['timeout' => 300, 'memory' => '1G']
     */
    public function getResourceLimits(string $binary): array
    {
        return match ($binary) {
            'npm', 'composer' => ['timeout' => 300, 'memory' => '1G'],
            'git' => ['timeout' => 120, 'memory' => '512M'],
            'php' => ['timeout' => 60, 'memory' => '256M'],
            default => ['timeout' => 30, 'memory' => '128M'],
        };
    }

    /**
     * Detect command injection attempts through dangerous shell operators.
     *
     * Allows safe pipe targets (grep, awk, sed, etc.) but blocks:
     * - Semicolon command separators (;)
     * - AND command chaining (&&)
     * - Command substitution ($(...))
     *
     * @param string $command Raw command to check
     * @return array{safe: bool, reason?: string} Safety result with optional reason
     *
     * Example - Safe pipe:
     *     detectInjectionAttempt('ls | grep foo');
     *     // ['safe' => true]
     *
     * Example - Dangerous chaining:
     *     detectInjectionAttempt('ls && rm -rf /');
     *     // ['safe' => false, 'reason' => 'AND command chaining']
     */
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

    /**
     * Parse command into binary and arguments.
     *
     * Splits command on whitespace, extracting binary name and arguments.
     *
     * @param string $command Raw command string
     * @return array{valid: bool, binary?: string, arguments?: string[], error?: string} Parse result
     *
     * Example:
     *     parseCommand('git commit -m "message"');
     *     // ['valid' => true, 'binary' => 'git', 'arguments' => ['commit', '-m', '"message"']]
     */
    private function parseCommand(string $command): array
    {
        $parts = preg_split('/\s+/', trim($command));

        return empty($parts) ? ['valid' => false, 'error' => 'Empty command'] : [
            'valid' => true,
            'binary' => $parts[0],
            'arguments' => array_slice($parts, 1),
        ];
    }

    /**
     * Validate command arguments based on binary-specific rules.
     *
     * Delegates to binary-specific validators:
     * - rm: Block recursive force deletion (-rf)
     * - git: Block force push (push --force)
     * - default: Allow all arguments
     *
     * @param string $binary Binary name
     * @param string[] $arguments Command arguments
     * @return array{valid: bool, errors?: string[], warnings?: string[]} Validation result
     */
    private function validateArguments(string $binary, array $arguments): array
    {
        return match ($binary) {
            'rm' => $this->validateRmArguments($arguments),
            'git' => $this->validateGitArguments($arguments),
            default => ['valid' => true],
        };
    }

    /**
     * Validate rm command arguments to prevent destructive operations.
     *
     * Blocks:
     * - Combined -rf flag
     * - Separate -r and -f flags
     *
     * @param string[] $arguments rm command arguments
     * @return array{valid: bool, errors?: string[]} Validation result
     */
    private function validateRmArguments(array $arguments): array
    {
        $hasRf = in_array('-rf', $arguments) || (in_array('-r', $arguments) && in_array('-f', $arguments));
        if ($hasRf) {
            return ['valid' => false, 'errors' => ['rm -rf is blocked']];
        }

        return ['valid' => true];
    }

    /**
     * Validate git command arguments to prevent dangerous operations.
     *
     * Blocks:
     * - Force push (push --force)
     *
     * @param string[] $arguments git command arguments
     * @return array{valid: bool, errors?: string[]} Validation result
     */
    private function validateGitArguments(array $arguments): array
    {
        if (str_contains(implode(' ', $arguments), 'push --force')) {
            return ['valid' => false, 'errors' => ['git push --force is blocked']];
        }

        return ['valid' => true];
    }

    /**
     * Sanitize parsed command for safe execution.
     *
     * Reconstructs command from validated binary and arguments.
     *
     * @param array{binary: string, arguments: string[]} $parsed Parsed command components
     * @return string Sanitized command string
     */
    private function sanitizeCommand(array $parsed): string
    {
        return $parsed['binary'].' '.implode(' ', $parsed['arguments']);
    }
}
