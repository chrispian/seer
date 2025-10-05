<?php

namespace HollisLabs\ToolCrate\Support;

use Symfony\Component\Process\Process;

class Exec
{
    public static function run(array $cmd, ?string $stdin = null, float $timeout = 8.0): object
    {
        $p = new Process($cmd);
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
