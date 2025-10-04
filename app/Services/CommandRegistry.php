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
        // 'session' => SessionCommand::class, // Migrated to YAML
        'recall' => RecallCommand::class,
        // 'bookmark' => BookmarkCommand::class, // Migrated to YAML
        // 'help' => HelpCommand::class, // Migrated to YAML
        // 'clear' => ClearCommand::class, // Migrated to YAML
        // 'frag' => FragCommand::class, // Migrated to YAML (simplified)
        // 'search' => SearchCommand::class, // Migrated to YAML (unified)
        // 's' => SearchCommand::class, // alias for search
        // 'todo' => TodoCommand::class, // Migrated to YAML (unified)
        // 't' => TodoCommand::class, // alias for todo
        // 'join' => JoinCommand::class, // Migrated to YAML
        // 'j' => JoinCommand::class, // alias for join - Migrated to YAML
        // 'channels' => ChannelsCommand::class, // Migrated to YAML
        // 'name' => NameCommand::class, // Migrated to YAML (simplified)
        // 'routing' => RoutingCommand::class, // Migrated to YAML

        // New CMD-01 commands
        'vault' => VaultCommand::class,
        'v' => VaultCommand::class, // alias for vault
        'project' => ProjectCommand::class,
        'p' => ProjectCommand::class, // alias for project
        'context' => ContextCommand::class,
        'ctx' => ContextCommand::class, // alias for context
        // 'inbox' => InboxCommand::class, // Migrated to YAML (unified)
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
