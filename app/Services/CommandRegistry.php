<?php

namespace App\Services;

use App\Actions\Commands\BookmarkCommand;
use App\Actions\Commands\ChannelsCommand;
use App\Actions\Commands\ClearCommand;
use App\Actions\Commands\ComposeCommand;
use App\Actions\Commands\ContextCommand;
use App\Actions\Commands\FragCommand;
use App\Actions\Commands\HelpCommand;
use App\Actions\Commands\InboxCommand;
use App\Actions\Commands\JoinCommand;
use App\Actions\Commands\NameCommand;
use App\Actions\Commands\ProjectCommand;
use App\Actions\Commands\RecallCommand;
use App\Actions\Commands\RoutingCommand;
use App\Actions\Commands\SearchCommand;
use App\Actions\Commands\SessionCommand;
use App\Actions\Commands\TodoCommand;
use App\Actions\Commands\VaultCommand;

class CommandRegistry
{
    protected static array $commands = [
        // Core commands
        'session' => SessionCommand::class,
        'recall' => RecallCommand::class,
        'bookmark' => BookmarkCommand::class,
        'help' => HelpCommand::class,
        'clear' => ClearCommand::class,
        'frag' => FragCommand::class,
        'search' => SearchCommand::class,
        's' => SearchCommand::class, // alias for search
        'todo' => TodoCommand::class,
        't' => TodoCommand::class, // alias for todo
        'join' => JoinCommand::class,
        'j' => JoinCommand::class, // alias for join
        'channels' => ChannelsCommand::class,
        'name' => NameCommand::class,
        'routing' => RoutingCommand::class,

        // New CMD-01 commands
        'vault' => VaultCommand::class,
        'v' => VaultCommand::class, // alias for vault
        'project' => ProjectCommand::class,
        'p' => ProjectCommand::class, // alias for project
        'context' => ContextCommand::class,
        'ctx' => ContextCommand::class, // alias for context
        'inbox' => InboxCommand::class,
        'in' => InboxCommand::class, // alias for inbox
        'compose' => ComposeCommand::class,
        'c' => ComposeCommand::class, // alias for compose

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
