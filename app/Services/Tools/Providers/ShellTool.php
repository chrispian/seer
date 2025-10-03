<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Config;

class ShellTool implements Tool
{
    public function slug(): string 
    { 
        return 'shell'; 
    }

    public function capabilities(): array 
    { 
        return ['exec', 'command']; 
    }

    public function isEnabled(): bool
    {
        return Config::get('fragments.tools.shell.enabled', false);
    }

    public function getConfigSchema(): array
    {
        return [
            'required' => ['cmd'],
            'properties' => [
                'cmd' => ['type' => 'string', 'description' => 'Shell command to execute'],
                'timeout' => ['type' => 'integer', 'default' => 15, 'description' => 'Timeout in seconds'],
                'workdir' => ['type' => 'string', 'description' => 'Working directory'],
            ]
        ];
    }

    public function call(array $args, array $context = []): array
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('Shell tool is disabled');
        }

        $cmd = $args['cmd'] ?? null;
        if (!$cmd) {
            throw new \InvalidArgumentException('Missing required parameter: cmd');
        }

        // Check against allowlist
        $allowlist = Config::get('fragments.tools.shell.allowlist', []);
        if (!empty($allowlist)) {
            $binary = explode(' ', trim($cmd))[0];
            if (!in_array($binary, $allowlist, true)) {
                throw new \RuntimeException("Command not allowed: {$binary}");
            }
        }

        $timeout = (int) ($args['timeout'] ?? Config::get('fragments.tools.shell.timeout_seconds', 15));
        $workdir = $args['workdir'] ?? Config::get('fragments.tools.shell.workdir');

        // Security: limit timeout
        $timeout = min($timeout, 300); // Max 5 minutes

        $process = Process::fromShellCommandline($cmd, $workdir, null, null, $timeout);
        $process->run();

        return [
            'exit_code' => $process->getExitCode(),
            'stdout' => substr($process->getOutput(), 0, 20000), // Limit output size
            'stderr' => substr($process->getErrorOutput(), 0, 20000),
            'success' => $process->isSuccessful(),
        ];
    }
}