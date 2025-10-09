<?php

namespace App\Services\Security;

use App\Models\CommandAuditLog;
use App\Services\Security\Guards\ResourceLimiter;
use App\Services\Security\Guards\ShellGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnhancedShellExecutor
{
    public function __construct(
        private ShellGuard $shellGuard,
        private ResourceLimiter $resourceLimiter
    ) {}

    /**
     * Execute shell command with full security stack
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
     * Execute command in dry-run mode
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
