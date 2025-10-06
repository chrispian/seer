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

// Orchestration commands
use App\Actions\Commands\SprintDetailCommand;
use App\Actions\Commands\SprintListCommand;
use App\Actions\Commands\TaskAssignCommand;
use App\Actions\Commands\TaskCreateCommand;
use App\Actions\Commands\TaskDetailCommand;
use App\Actions\Commands\TaskListCommand;
use App\Actions\Commands\BacklogListCommand;
use App\Actions\Commands\AgentListCommand;
use App\Actions\Commands\AiLogsCommand;

class CommandRegistry
{
    protected static array $commands = [
        // Core commands  
        // 'session' => SessionCommand::class, // MIGRATED: Using YAML v2.0.0
        // 'recall' => RecallCommand::class, // MIGRATED: Using YAML v1.0.0/v2.0.0
        // 'bookmark' => BookmarkCommand::class, // MIGRATED: Using YAML v2.0.0
        'help' => HelpCommand::class, // KEEP: Enhanced version with tool discovery
        // 'clear' => ClearCommand::class, // MIGRATED: Using YAML v2.0.0  
        // 'frag' => FragCommand::class, // MIGRATED: Using YAML v2.0.0
        // 'search' => SearchCommand::class, // MIGRATED: Using YAML v2.0.0 
        // 's' => SearchCommand::class, // MIGRATED: Using alias resolution in CommandController
        // 'todo' => TodoCommand::class, // MIGRATED: Using YAML v3.0.0
        // 't' => TodoCommand::class, // MIGRATED: Using alias resolution in CommandController
        // 'join' => JoinCommand::class, // MIGRATED: Using YAML v2.0.0
        // 'j' => JoinCommand::class, // MIGRATED: Using alias resolution in CommandController
        // 'channels' => ChannelsCommand::class, // MIGRATED: Using YAML v2.0.0
        // 'name' => NameCommand::class, // MIGRATED: Using YAML v2.0.0
        // 'routing' => RoutingCommand::class, // MIGRATED: Using YAML v2.0.0

        // New CMD-01 commands
        'vault' => VaultCommand::class,
        'v' => VaultCommand::class, // alias for vault
        'project' => ProjectCommand::class,
        'p' => ProjectCommand::class, // alias for project
        'context' => ContextCommand::class,
        'ctx' => ContextCommand::class, // alias for context
        'inbox' => InboxCommand::class, // TODO: Migrate to YAML (unified)
        'in' => InboxCommand::class, // alias for inbox
        'compose' => ComposeCommand::class,
        'c' => ComposeCommand::class, // alias for compose

        // Orchestration commands
        'sprint-detail' => SprintDetailCommand::class,
        'sd' => SprintDetailCommand::class, // alias for sprint-detail
        'sprint-list' => SprintListCommand::class,
        'sl' => SprintListCommand::class, // alias for sprint-list
        'sprints' => SprintListCommand::class, // default to list
        'task-assign' => TaskAssignCommand::class,
        'ta' => TaskAssignCommand::class, // alias for task-assign
        'task-create' => TaskCreateCommand::class,
        'tc' => TaskCreateCommand::class, // alias for task-create
        'task-detail' => TaskDetailCommand::class,
        'td' => TaskDetailCommand::class, // alias for task-detail
        'task-list' => TaskListCommand::class,
        'tl' => TaskListCommand::class, // alias for task-list
        'tasks' => TaskListCommand::class, // default to list
        'backlog-list' => BacklogListCommand::class,
        'bl' => BacklogListCommand::class, // alias for backlog-list
        'agent-list' => AgentListCommand::class,
        'al' => AgentListCommand::class, // alias for agent-list
        'agents' => AgentListCommand::class, // default to list
        'ailogs' => AiLogsCommand::class, // AI logs viewer

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
