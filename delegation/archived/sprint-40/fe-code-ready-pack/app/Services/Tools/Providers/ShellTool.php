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
        return ['exec'];
    }

    public function call(array $args, array $context = []): array
    {
        if (! Config::get('fragments.tools.shell.enabled')) {
            throw new \RuntimeException('Shell tool disabled');
        }
        $cmd = $args['cmd'] ?? null;
        if (! $cmd) {
            throw new \InvalidArgumentException('Missing cmd');
        }

        $allow = Config::get('fragments.tools.shell.allowlist', []);
        $bin = explode(' ', trim($cmd))[0];
        if (! in_array($bin, $allow, true)) {
            throw new \RuntimeException("Command not allowed: {$bin}");
        }

        $timeout = (int) Config::get('fragments.tools.shell.timeout_seconds', 15);
        $cwd = Config::get('fragments.tools.shell.workdir');

        $p = Process::fromShellCommandline($cmd, $cwd, null, null, $timeout);
        $p->run();

        return [
            'exit_code' => $p->getExitCode(),
            'stdout' => substr($p->getOutput(), 0, 20000),
            'stderr' => substr($p->getErrorOutput(), 0, 20000),
        ];
    }
}
