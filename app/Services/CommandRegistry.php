<?php

namespace App\Services;

class CommandRegistry
{
    /**
     * PHP-based command registry (primary system after migration)
     * Maps command slugs to PHP class implementations
     */
    protected static array $phpCommands = [
        // Help & System
        'help' => \App\Commands\HelpCommand::class,

        // Orchestration Commands - Sprints
        'sprints' => \App\Commands\Orchestration\Sprint\ListCommand::class,
        'sprint-list' => \App\Commands\Orchestration\Sprint\ListCommand::class,
        'sl' => \App\Commands\Orchestration\Sprint\ListCommand::class,
        'sprint-detail' => \App\Commands\Orchestration\Sprint\DetailCommand::class,
        'sd' => \App\Commands\Orchestration\Sprint\DetailCommand::class,

        // Orchestration Commands - Tasks
        'tasks' => \App\Commands\Orchestration\Task\ListCommand::class,
        'task-list' => \App\Commands\Orchestration\Task\ListCommand::class,
        'tl' => \App\Commands\Orchestration\Task\ListCommand::class,
        'task-detail' => \App\Commands\Orchestration\Task\DetailCommand::class,
        'td' => \App\Commands\Orchestration\Task\DetailCommand::class,

        // Orchestration Commands - Backlog
        'backlog-list' => \App\Commands\Orchestration\Backlog\ListCommand::class,
        'backlog' => \App\Commands\Orchestration\Backlog\ListCommand::class,
        'bl' => \App\Commands\Orchestration\Backlog\ListCommand::class,

        // Orchestration Commands - Agents
        'agents' => \App\Commands\Orchestration\Agent\ListCommand::class,
        'agent-list' => \App\Commands\Orchestration\Agent\ListCommand::class,
        'agent-profiles' => \App\Commands\Orchestration\Agent\ListCommand::class,
        'ap' => \App\Commands\Orchestration\Agent\ListCommand::class,
        'al' => \App\Commands\Orchestration\Agent\ListCommand::class,

        // Fragment & Content Commands
        'search' => \App\Commands\SearchCommand::class,
        's' => \App\Commands\SearchCommand::class,
        'recall' => \App\Commands\RecallCommand::class,
        'inbox' => \App\Commands\InboxCommand::class,
        'in' => \App\Commands\InboxCommand::class,
        'frag' => \App\Commands\FragCommand::class,
        'frag-simple' => \App\Commands\FragSimpleCommand::class,

        // Bookmark & Collections
        'bookmark' => \App\Commands\BookmarkListCommand::class,
        'bm' => \App\Commands\BookmarkListCommand::class,

        // Session & Navigation
        'session' => \App\Commands\SessionListCommand::class,
        'sessions' => \App\Commands\SessionListCommand::class,
        'channels' => \App\Commands\ChannelsCommand::class,

        // Todo & Notes
        'todo' => \App\Commands\TodoCommand::class,
        't' => \App\Commands\TodoCommand::class,
        'notes' => \App\Commands\NoteListCommand::class,
        'note' => \App\Commands\NoteCommand::class,

        // Utility Commands
        'clear' => \App\Commands\ClearCommand::class,
        'join' => \App\Commands\JoinCommand::class,
        'j' => \App\Commands\JoinCommand::class,
        'name' => \App\Commands\NameCommand::class,
        'routing' => \App\Commands\RoutingCommand::class,
        'context' => \App\Commands\ContextCommand::class,
        'ctx' => \App\Commands\ContextCommand::class,
        'compose' => \App\Commands\ComposeCommand::class,
        'c' => \App\Commands\ComposeCommand::class,

        // Vault & Project Management
        'vault' => \App\Commands\VaultListCommand::class,
        'vaults' => \App\Commands\VaultListCommand::class,
        'v' => \App\Commands\VaultListCommand::class,
        'project' => \App\Commands\ProjectListCommand::class,
        'projects' => \App\Commands\ProjectListCommand::class,
        'p' => \App\Commands\ProjectListCommand::class,

        // Type Management
        'types' => \App\Commands\TypeManagementCommand::class,

        // Additional Commands
        'accept' => \App\Commands\AcceptCommand::class,
        'approve' => \App\Commands\AcceptCommand::class,
        'link' => \App\Commands\LinkCommand::class,
        'remind' => \App\Commands\RemindCommand::class,
        'reminder' => \App\Commands\RemindCommand::class,
        'news-digest' => \App\Commands\NewsDigestCommand::class,
        'digest' => \App\Commands\NewsDigestCommand::class,
        'news' => \App\Commands\NewsDigestCommand::class,
        'setup' => \App\Commands\SetupCommand::class,
        'onboard' => \App\Commands\SetupCommand::class,
        'configure' => \App\Commands\SetupCommand::class,
        'schedule-list' => \App\Commands\ScheduleListCommand::class,
        'schedules' => \App\Commands\ScheduleListCommand::class,
    ];

    public static function isPhpCommand(string $commandName): bool
    {
        return array_key_exists($commandName, self::$phpCommands);
    }

    public static function getPhpCommand(string $commandName): string
    {
        if (! self::isPhpCommand($commandName)) {
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
                if (! in_array($className, $seenClasses)) {
                    $aliases = array_keys(array_filter(self::$phpCommands, fn ($class) => $class === $className));
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

    public static function getCommandsByCategory(?string $category = null): array
    {
        $commands = self::getAllCommandsWithHelp();

        if ($category) {
            return array_filter($commands, fn ($cmd) => strtolower($cmd['category']) === strtolower($category)
            );
        }

        return $commands;
    }

    public static function find(string $commandName): string
    {
        logger('COMMAND_REGISTRY_LOOKUP', ['commandName' => $commandName]);
        if (! array_key_exists($commandName, self::$phpCommands)) {
            throw new \InvalidArgumentException("Command not recognized: {$commandName}");
        }

        return self::$phpCommands[$commandName];
    }

    public static function all(): array
    {
        return array_keys(self::$phpCommands);
    }
}
