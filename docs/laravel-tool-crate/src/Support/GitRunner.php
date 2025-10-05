<?php

namespace HollisLabs\ToolCrate\Support;

class GitRunner
{
    private static ?bool $gh = null;
    private static ?bool $git = null;

    public static function hasGh(): bool
    {
        if (self::$gh !== null) return self::$gh;
        $res = Exec::run(['gh', '--version'], null, 3.0);
        return self::$gh = $res->ok;
    }

    public static function hasGit(): bool
    {
        if (self::$git !== null) return self::$git;
        $res = Exec::run(['git', '--version'], null, 3.0);
        return self::$git = $res->ok;
    }

    public static function run(array $cmd, ?string $stdin = null, ?string $cwd = null, float $timeout = 20.0): object
    {
        return Exec::run($cmd, $stdin, $timeout, $cwd);
    }
}
