<?php

namespace HollisLabs\ToolCrate\Support;

use Symfony\Component\Process\Process;

class Exec
{
    public static function run(array $cmd, ?string $stdin = null, float $timeout = 12.0, ?string $cwd = null): object
    {
        $p = new Process($cmd, $cwd ?: null);
        $p->setTimeout($timeout);
        if ($stdin !== null) $p->setInput($stdin);
        $p->run();

        return (object) [
            'ok' => $p->isSuccessful(),
            'stdout' => $p->getOutput(),
            'stderr' => $p->getErrorOutput(),
            'code' => $p->getExitCode(),
        ];
    }
}
