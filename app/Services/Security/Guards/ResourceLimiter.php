<?php

namespace App\Services\Security\Guards;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ResourceLimiter
{
    public function executeWithLimits(string $command, array $limits, ?string $workdir = null): array
    {
        $timeout = $limits['timeout'] ?? 30;
        $memory = $limits['memory'] ?? '128M';
        
        $limitedCommand = $this->wrapWithLimits($command, $memory);
        
        $process = Process::fromShellCommandline($limitedCommand, $workdir, null, null, $timeout);
        
        $startTime = microtime(true);
        $process->run();
        $executionTime = (int)((microtime(true) - $startTime) * 1000);

        return [
            'exit_code' => $process->getExitCode(),
            'stdout' => substr($process->getOutput(), 0, 50000),
            'stderr' => substr($process->getErrorOutput(), 0, 50000),
            'success' => $process->isSuccessful(),
            'execution_time_ms' => $executionTime,
            'memory_limit' => $memory,
        ];
    }

    private function wrapWithLimits(string $command, string $memoryLimit): string
    {
        $memoryKb = $this->parseMemoryLimit($memoryLimit) / 1024;
        
        if (PHP_OS_FAMILY === 'Darwin') {
            return "ulimit -t 60 -m {$memoryKb} && {$command}";
        } else {
            return "ulimit -t 60 -v {$memoryKb} && {$command}";
        }
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = strtoupper(trim($limit));
        
        if (preg_match('/^(\d+)([KMG]?)$/', $limit, $matches)) {
            $value = (int)$matches[1];
            $unit = $matches[2] ?? '';
            
            return match($unit) {
                'K' => $value * 1024,
                'M' => $value * 1024 * 1024,
                'G' => $value * 1024 * 1024 * 1024,
                default => $value,
            };
        }

        return 128 * 1024 * 1024;
    }
}
