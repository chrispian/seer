<?php

declare(strict_types=1);

namespace App\Services\Security\Guards;

use Symfony\Component\Process\Process;

/**
 * Resource-limited command execution guard.
 *
 * Executes shell commands with resource limits:
 * - Timeout (execution time limit)
 * - Memory (virtual memory limit)
 * - Output truncation (50KB stdout/stderr max)
 *
 * Uses platform-specific ulimit wrapping:
 * - macOS: CPU time limit only (ulimit -t)
 * - Linux: CPU time + virtual memory (ulimit -t -v)
 *
 * Example:
 *     $limiter = new ResourceLimiter();
 *     $result = $limiter->executeWithLimits('npm install', [
 *         'timeout' => 300,
 *         'memory' => '1G'
 *     ], '/path/to/project');
 *
 * @see ShellGuard For command validation before execution
 * @see EnhancedShellExecutor For orchestrated execution
 */
class ResourceLimiter
{
    /**
     * Default execution timeout in seconds.
     * Commands exceeding this limit will be terminated.
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Default memory limit for command execution.
     * Prevents runaway processes from consuming excessive memory.
     */
    private const DEFAULT_MEMORY = '128M';

    /**
     * Maximum output length for stdout/stderr in bytes (50KB).
     * Output beyond this limit is truncated to prevent memory issues.
     */
    private const MAX_OUTPUT_LENGTH = 50000;

    /**
     * CPU time limit in seconds for ulimit wrapper.
     * Hard limit on CPU time regardless of wall-clock timeout.
     */
    private const ULIMIT_CPU_TIME = 60;

    /**
     * Default memory limit in bytes when parsing fails (128MB).
     * Fallback value to ensure commands don't run unlimited.
     */
    private const DEFAULT_MEMORY_BYTES = 128 * 1024 * 1024;
    /**
     * Execute command with resource limits enforced.
     *
     * Wraps command with ulimit and executes via Symfony Process.
     * Output is truncated to 50KB to prevent memory issues.
     *
     * @param string $command Shell command to execute
     * @param array<string, mixed> $limits Resource limits:
     *        - 'timeout' (int): Execution timeout in seconds (default: 30)
     *        - 'memory' (string): Memory limit (e.g., '128M', '1G', default: '128M')
     * @param string|null $workdir Working directory (null = current directory)
     * @return array{
     *     exit_code: int,
     *     stdout: string,
     *     stderr: string,
     *     success: bool,
     *     execution_time_ms: int,
     *     memory_limit: string
     * } Execution result
     *
     * Example:
     *     $result = $limiter->executeWithLimits('git clone repo', ['timeout' => 120, 'memory' => '512M']);
     *     // ['success' => true, 'exit_code' => 0, 'stdout' => '...', ...]
     */
    public function executeWithLimits(string $command, array $limits, ?string $workdir = null): array
    {
        $timeout = $limits['timeout'] ?? self::DEFAULT_TIMEOUT;
        $memory = $limits['memory'] ?? self::DEFAULT_MEMORY;

        $limitedCommand = $this->wrapWithLimits($command, $memory);

        $process = Process::fromShellCommandline($limitedCommand, $workdir, null, null, $timeout);

        $startTime = microtime(true);
        $process->run();
        $executionTime = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'exit_code' => $process->getExitCode(),
            'stdout' => substr($process->getOutput(), 0, self::MAX_OUTPUT_LENGTH),
            'stderr' => substr($process->getErrorOutput(), 0, self::MAX_OUTPUT_LENGTH),
            'success' => $process->isSuccessful(),
            'execution_time_ms' => $executionTime,
            'memory_limit' => $memory,
        ];
    }

    /**
     * Wrap command with platform-specific ulimit resource limits.
     *
     * - macOS: ulimit -t 60 (CPU time only)
     * - Linux: ulimit -t 60 -v {memory_kb} (CPU time + virtual memory)
     *
     * @param string $command Command to wrap
     * @param string $memoryLimit Memory limit (e.g., '128M', '1G')
     * @return string Wrapped command with ulimit prefix
     */
    private function wrapWithLimits(string $command, string $memoryLimit): string
    {
        $memoryKb = (int) ($this->parseMemoryLimit($memoryLimit) / 1024);

        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS doesn't support all ulimit options reliably
            // Just use CPU time limit and let command run
            return 'ulimit -t '.self::ULIMIT_CPU_TIME." 2>/dev/null; {$command}";
        } else {
            // Linux supports virtual memory limit
            return 'ulimit -t '.self::ULIMIT_CPU_TIME." -v {$memoryKb} 2>/dev/null && {$command}";
        }
    }

    /**
     * Parse memory limit string to bytes.
     *
     * Supports:
     * - Bytes: '1024' -> 1024
     * - Kilobytes: '128K' -> 131072
     * - Megabytes: '256M' -> 268435456
     * - Gigabytes: '1G' -> 1073741824
     *
     * @param string $limit Memory limit string
     * @return int Memory limit in bytes (default: 128MB if parse fails)
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = strtoupper(trim($limit));

        if (preg_match('/^(\d+)([KMG]?)$/', $limit, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2] ?? '';

            return match ($unit) {
                'K' => $value * 1024,
                'M' => $value * 1024 * 1024,
                'G' => $value * 1024 * 1024 * 1024,
                default => $value,
            };
        }

        return self::DEFAULT_MEMORY_BYTES;
    }
}
