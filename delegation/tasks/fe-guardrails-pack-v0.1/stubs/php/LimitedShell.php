<?php

namespace App\Security;

use Symfony\Component\Process\Process;

class LimitedShell
{
    public function __construct(
        private array $commandSpec,    // allowlist DSL loaded from config
        private int $timeoutSeconds = 30,
        private int $outputByteCap = 1_000_000 // 1MB
    ) {}

    public function run(string $bin, array $args = [], array $opts = []): array
    {
        $spec = $this->validateSpec($bin, $args);
        $cwd = $opts['cwd'] ?? $spec['cwd'] ?? base_path('storage/agents/default');
        $env = $this->scrubEnv($opts['env'] ?? []);

        $cmd = array_merge([$bin], $spec['fixed_flags'] ?? [], $this->validateArgs($spec, $args));

        $process = new Process($cmd, $cwd, $env, null, $this->timeoutSeconds);
        $process->setPty(False);
        $process->start();

        $out = '';
        $err = '';
        while ($process->isRunning()) {
            $out .= $process->getIncrementalOutput();
            $err .= $process->getIncrementalErrorOutput();
            if (strlen($out) + strlen($err) > $this->outputByteCap) {
                $process->stop(0.2, SIGKILL);
                throw new \RuntimeException('Output cap exceeded');
            }
            usleep(50_000);
        }

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Command failed: ' . $process->getErrorOutput());
        }

        return ['stdout' => $out, 'stderr' => $err, 'exit' => $process->getExitCode()];
    }

    private function validateSpec(string $bin, array $args): array
    {
        $spec = $this->commandSpec[$bin] ?? null;
        if (!$spec) throw new \InvalidArgumentException('Command not allowed');
        // TODO: add per-arg schema validation and deny unknown flags.
        return $spec;
    }

    private function validateArgs(array $spec, array $args): array
    {
        // TODO: schema-based validation (types, patterns, path allowlist)
        // Reject absolute paths unless allowed; normalize/realpath and check policy.
        return $args;
    }

    private function scrubEnv(array $env): array
    {
        $safe = [];
        $allow = $this->commandSpec['_env_allow'] ?? ['LANG'];
        foreach ($allow as $k) {
            if (isset($env[$k])) $safe[$k] = $env[$k];
        }
        return $safe;
    }
}
