<?php

namespace App\Services;

use App\Actions\Commands\BookmarkCommand;
use App\Actions\Commands\ChaosCommand;
use App\Actions\Commands\ClearCommand;
use App\Actions\Commands\FragCommand;
use App\Actions\Commands\HelpCommand;
use App\Actions\Commands\RecallCommand;
use App\Actions\Commands\SearchCommand;
use App\Actions\Commands\SessionCommand;
use App\Actions\Commands\TodoCommand;
use App\Actions\Commands\JoinCommand;
use App\Actions\Commands\ChannelsCommand;
use App\Actions\Commands\NameCommand;

class CommandRegistry
{
    protected static array $commands = [
        'session' => SessionCommand::class,
        'recall' => RecallCommand::class,
        'bookmark' => BookmarkCommand::class,
        'help' => HelpCommand::class,
        'clear' => ClearCommand::class,
        'frag' => FragCommand::class,
        'chaos' => ChaosCommand::class,
        'search' => SearchCommand::class,
        's' => SearchCommand::class, // alias for search
        'todo' => TodoCommand::class,
        't' => TodoCommand::class, // alias for todo
        'join' => JoinCommand::class,
        'j' => JoinCommand::class, // alias for join
        'channels' => ChannelsCommand::class,
        'name' => NameCommand::class,
        // 'export' => ExportCommand::class (future)
    ];

    public static function find(string $commandName): string
    {
        logger('COMMAND_REGISTRY_LOOKUP', ['commandName' => $commandName]);
        if (! array_key_exists($commandName, self::$commands)) {
            throw new \InvalidArgumentException("Command not recognized: {$commandName}");
        }

        return self::$commands[$commandName];
    }

    public static function all(): array
    {
        return array_keys(self::$commands);
    }
}
