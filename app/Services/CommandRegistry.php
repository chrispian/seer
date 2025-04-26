<?php

namespace App\Services;

use App\Actions\Commands\BookmarkCommand;
use App\Actions\Commands\ClearCommand;
use App\Actions\Commands\FragCommand;
use App\Actions\Commands\HelpCommand;
use App\Actions\Commands\RecallCommand;
use App\Actions\Commands\SessionCommand;

class CommandRegistry
{
    protected static array $commands = [
        'session' => SessionCommand::class,
        'recall' => RecallCommand::class,
        'bookmark' => BookmarkCommand::class,
        'help' => HelpCommand::class,
        'clear' => ClearCommand::class,
        'frag' => FragCommand::class,
        'chaos' => FragCommand::class,
        // 'export' => ExportCommand::class (future)
    ];

    public static function find(string $commandName): string
    {
        logger('COMMAND_REGISTRY_LOOKUP', ['commandName' => $commandName]);
        if (!array_key_exists($commandName, self::$commands)) {
            throw new \InvalidArgumentException("Unknown command: {$commandName}");
        }

        return self::$commands[$commandName];
    }

    public static function all(): array
    {
        return array_keys(self::$commands);
    }


}
