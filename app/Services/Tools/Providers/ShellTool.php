<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;

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
            ],
        ];
    }

    public function call(array $args, array $context = []): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('Shell tool is disabled');
        }

        $cmd = $args['cmd'] ?? null;
        if (! $cmd) {
            throw new \InvalidArgumentException('Missing required parameter: cmd');
        }

        // Check for destructive database operations
        if ($this->requiresUserConfirmation($cmd)) {
            throw new \RuntimeException(
                'This command requires explicit user confirmation because it may perform destructive operations. '.
                'Please confirm you want to execute: '.substr($cmd, 0, 100)
            );
        }

        $this->checkForDestructiveOperations($cmd);

        // Check against allowlist and prevent command injection
        $allowlist = Config::get('fragments.tools.shell.allowlist', []);
        if (! empty($allowlist)) {
            $binary = explode(' ', trim($cmd))[0];
            if (! in_array($binary, $allowlist, true)) {
                throw new \RuntimeException("Command not allowed: {$binary}");
            }

            // Security: Prevent command injection by rejecting shell control characters
            $dangerousChars = ['&', '|', ';', '`', '$', '(', ')', '<', '>', '"', "'", '\\', "\n", "\r"];
            foreach ($dangerousChars as $char) {
                if (strpos($cmd, $char) !== false) {
                    throw new \RuntimeException('Command contains dangerous characters and is not allowed');
                }
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

    /**
     * Check if the command requires user confirmation
     */
    protected function requiresUserConfirmation(string $cmd): bool
    {
        $confirmationPatterns = [
            // Database operations that modify data
            '/php artisan migrate/',
            '/artisan migrate/',
            '/php artisan db:/',
            '/artisan db:/',
            '/composer.*install/',
            '/composer.*update/',
            '/npm.*install/',
            '/npm.*update/',
            '/yarn.*add/',
            '/git.*push/',
            '/git.*pull/',
            '/rm.*-rf/',
            '/mysql/',
            '/psql/',
        ];

        foreach ($confirmationPatterns as $pattern) {
            if (preg_match($pattern, $cmd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the command contains destructive database operations
     */
    protected function checkForDestructiveOperations(string $cmd): void
    {
        $destructivePatterns = [
            // Laravel artisan commands
            '/php artisan migrate:fresh/',
            '/php artisan db:wipe/',
            '/php artisan migrate:reset/',
            '/php artisan migrate:rollback/',
            '/artisan migrate:fresh/',
            '/artisan db:wipe/',
            '/artisan migrate:reset/',
            '/artisan migrate:rollback/',

            // Raw SQL destructive operations
            '/DROP DATABASE/',
            '/DROP TABLE/',
            '/TRUNCATE TABLE/',
            '/DELETE FROM.*WHERE.*=.*1.*=.*1/', // Attempt to catch DELETE without WHERE

            // MySQL/PostgreSQL specific
            '/mysql.*-e.*DROP/',
            '/psql.*-c.*DROP/',
            '/mysql.*-e.*TRUNCATE/',
            '/psql.*-c.*TRUNCATE/',
        ];

        foreach ($destructivePatterns as $pattern) {
            if (preg_match($pattern, $cmd)) {
                throw new \RuntimeException(
                    'Command contains destructive database operation and is blocked. '.
                    'Destructive operations require explicit user approval. '.
                    'Command blocked: '.substr($cmd, 0, 100)
                );
            }
        }

        // Additional check for raw DB facade usage in PHP files
        if (str_contains($cmd, 'DB::') && (str_contains($cmd, 'truncate') || str_contains($cmd, 'delete') || str_contains($cmd, 'drop'))) {
            throw new \RuntimeException(
                'Command appears to contain raw database destructive operations and is blocked. '.
                'Use of DB facade for destructive operations requires explicit user approval.'
            );
        }
    }
}
