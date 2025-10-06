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
        'sprints' => SprintListCommand::class,
        'task-list' => TaskListCommand::class, // default to list
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

    // New PHP command system methods
    protected static array $phpCommands = [
        'help' => \App\Commands\HelpCommand::class,
        'sprints' => \App\Commands\SprintListCommand::class,
        'sprint-list' => \App\Commands\SprintListCommand::class,  // Alias
        'tasks' => \App\Commands\TaskListCommand::class,
        'task-list' => \App\Commands\TaskListCommand::class,      // Alias
        'agents' => \App\Commands\AgentListCommand::class,
        'agent-list' => \App\Commands\AgentListCommand::class,    // Alias
        'backlog-list' => \App\Commands\BacklogListCommand::class,
        'bookmark' => \App\Commands\BookmarkListCommand::class,
        'recall' => \App\Commands\RecallCommand::class,
        'search' => \App\Commands\SearchCommand::class,
        's' => \App\Commands\SearchCommand::class,               // Alias
        'session' => \App\Commands\SessionListCommand::class,
        'join' => \App\Commands\JoinCommand::class,
        'j' => \App\Commands\JoinCommand::class,                 // Alias
        'channels' => \App\Commands\ChannelsCommand::class,
        'clear' => \App\Commands\ClearCommand::class,
        'frag' => \App\Commands\FragCommand::class,
        'todo' => \App\Commands\TodoCommand::class,
        'todo list' => \App\Commands\TodoCommand::class,        // Hook alias
        't' => \App\Commands\TodoCommand::class,                 // Alias
        'name' => \App\Commands\NameCommand::class,
        'routing' => \App\Commands\RoutingCommand::class,
        'vault' => \App\Commands\VaultListCommand::class,
        'v' => \App\Commands\VaultListCommand::class,             // Alias
        'project' => \App\Commands\ProjectListCommand::class,
        'p' => \App\Commands\ProjectListCommand::class,          // Alias
        'context' => \App\Commands\ContextCommand::class,
        'ctx' => \App\Commands\ContextCommand::class,            // Alias
        'inbox' => \App\Commands\InboxCommand::class,
        'in' => \App\Commands\InboxCommand::class,               // Alias
        'compose' => \App\Commands\ComposeCommand::class,
        'c' => \App\Commands\ComposeCommand::class,              // Alias
    ];

    public static function isPhpCommand(string $commandName): bool
    {
        return array_key_exists($commandName, self::$phpCommands);
    }

    public static function getPhpCommand(string $commandName): string
    {
        if (!self::isPhpCommand($commandName)) {
            throw new \InvalidArgumentException("PHP Command not recognized: {$commandName}");
        }
        return self::$phpCommands[$commandName];
    }

    public static function getAllPhpCommands(): array
    {
        return self::$phpCommands;
    }

    public static function getAllCommandsWithHelp(): array
    {
        $commands = [];
        $seenClasses = [];
        
        foreach (self::$phpCommands as $slug => $className) {
            if (class_exists($className)) {
                // Only include each class once (avoid alias duplicates)
                if (!in_array($className, $seenClasses)) {
                    $aliases = array_keys(array_filter(self::$phpCommands, fn($class) => $class === $className));
                    $mainSlug = $aliases[0]; // Use first occurrence as main
                    $aliasesForDisplay = array_slice($aliases, 1); // Get remaining as aliases
                    
                    $commands[] = [
                        'slug' => $mainSlug,
                        'name' => $className::getName(),
                        'description' => $className::getDescription(),
                        'usage' => $className::getUsage(),
                        'category' => $className::getCategory(),
                        'aliases' => $aliasesForDisplay,
                    ];
                    
                    $seenClasses[] = $className;
                }
            }
        }
        
        return $commands;
    }
    
    public static function getCommandsByCategory(string $category = null): array
    {
        $commands = self::getAllCommandsWithHelp();
        
        if ($category) {
            return array_filter($commands, fn($cmd) => 
                strtolower($cmd['category']) === strtolower($category)
            );
        }
        
        return $commands;
    }

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
