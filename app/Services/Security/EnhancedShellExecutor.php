<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\CommandAuditLog;
use App\Services\Security\Guards\ResourceLimiter;
use App\Services\Security\Guards\ShellGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced shell command executor with security validation and audit logging.
 *
 * Orchestrates secure command execution:
 * - Security validation (ShellGuard)
 * - Resource limiting (timeout, memory)
 * - Audit logging (CommandAuditLog)
 * - Exception handling
 * - Dry-run simulation
 *
 * Example:
 *     $executor = new EnhancedShellExecutor($shellGuard, $resourceLimiter);
 *     $result = $executor->execute('git status', ['timeout' => 30]);
 *     if ($result['success']) {
 *         echo $result['stdout'];
 *     }
 *
 * @see ShellGuard For command validation
 * @see ResourceLimiter For resource-limited execution
 * @see CommandAuditLog For audit trail
 */
class EnhancedShellExecutor
{
    public function __construct(
        private ShellGuard $shellGuard,
        private ResourceLimiter $resourceLimiter
    ) {}

    /**
     * Execute shell command with full security stack.
     *
     * Pipeline:
     * 1. Validate command (ShellGuard)
     * 2. Create audit log entry
     * 3. Get resource limits
     * 4. Execute with limits (ResourceLimiter)
     * 5. Update audit log with results
     *
     * @param string $command Shell command to execute
     * @param array<string, mixed> $options Execution options:
     *        - 'context' (array): Validation context (approved, user_id)
     *        - 'timeout' (int): Timeout override (capped by default limits)
     *        - 'workdir' (string): Working directory
     * @return array{
     *     success: bool,
     *     exit_code: int,
     *     stdout: string,
     *     stderr: string,
     *     blocked?: bool,
     *     violations?: string[],
     *     audit_log_id?: int,
     *     warnings?: string[],
     *     exception?: bool,
     *     execution_time_ms?: int,
     *     memory_limit?: string
     * } Execution result
     *
     * Example - Successful execution:
     *     $result = $executor->execute('git status');
     *     // ['success' => true, 'exit_code' => 0, 'stdout' => '...', ...]
     *
     * Example - Blocked command:
     *     $result = $executor->execute('rm -rf /');
     *     // ['success' => false, 'blocked' => true, 'violations' => [...], ...]
     */
    public function execute(string $command, array $options = []): array
    {
        $startTime = microtime(true);
        $auditLog = null;

        try {
            // 1. Validate command
            $validation = $this->shellGuard->validateCommand($command, $options['context'] ?? []);

            if (! $validation['allowed']) {
                return [
                    'success' => false,
                    'exit_code' => -1,
                    'stdout' => '',
                    'stderr' => 'BLOCKED: '.implode('; ', $validation['violations']),
                    'blocked' => true,
                    'violations' => $validation['violations'],
                ];
            }

            // 2. Create audit log entry
            $binary = explode(' ', trim($command))[0];
            $auditLog = CommandAuditLog::create([
                'command_name' => $binary,
                'command_signature' => $command,
                'status' => 'running',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'started_at' => now(),
            ]);

            // 3. Get resource limits
            $limits = $this->shellGuard->getResourceLimits($binary);
            if (isset($options['timeout'])) {
                $limits['timeout'] = min($options['timeout'], $limits['timeout']);
            }

            // 4. Execute with limits
            $result = $this->resourceLimiter->executeWithLimits(
                $validation['sanitized_command'] ?? $command,
                $limits,
                $options['workdir'] ?? null
            );

            // 5. Update audit log
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($auditLog) {
                $auditLog->update([
                    'status' => $result['success'] ? 'completed' : 'failed',
                    'exit_code' => $result['exit_code'],
                    'output' => substr($result['stdout'], 0, 10000),
                    'error_output' => substr($result['stderr'], 0, 10000),
                    'execution_time_ms' => $executionTime,
                    'completed_at' => now(),
                ]);
            }

            $result['audit_log_id'] = $auditLog?->id;
            $result['warnings'] = $validation['warnings'] ?? [];

            return $result;

        } catch (\Exception $e) {
            if ($auditLog) {
                $auditLog->update([
                    'status' => 'failed',
                    'error_output' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            Log::error('Shell execution failed', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'exit_code' => -1,
                'stdout' => '',
                'stderr' => $e->getMessage(),
                'exception' => true,
            ];
        }
    }

    /**
     * Execute command in dry-run mode (validation only, no execution).
     *
     * Returns validation result and resource limits without executing.
     *
     * @param string $command Shell command to validate
     * @param array<string, mixed> $options Validation options (see execute)
     * @return array{
     *     would_execute: bool,
     *     validation: array,
     *     resource_limits: array
     * } Dry-run result
     */
    public function dryRun(string $command, array $options = []): array
    {
        $validation = $this->shellGuard->validateCommand($command, $options['context'] ?? []);

        return [
            'would_execute' => $validation['allowed'],
            'validation' => $validation,
            'resource_limits' => $this->shellGuard->getResourceLimits(
                explode(' ', trim($command))[0]
            ),
        ];
    }
}
