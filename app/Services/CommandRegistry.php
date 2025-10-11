<?php

namespace App\Services;

use App\Models\Command;
use Illuminate\Support\Facades\Cache;

class CommandRegistry
{
    protected static ?array $commandsCache = null;
    
    protected static int $cacheTtl = 3600;

    protected static function loadCommands(): array
    {
        if (self::$commandsCache !== null) {
            return self::$commandsCache;
        }

        self::$commandsCache = Cache::remember('command_registry', self::$cacheTtl, function () {
            $commands = [];
            
            Command::active()
                ->with('type')
                ->get()
                ->each(function ($command) use (&$commands) {
                    $commandSlug = ltrim($command->command, '/');
                    $commands[$commandSlug] = [
                        'handler_class' => $command->handler_class,
                        'command' => $command,
                        'type' => $command->type,
                    ];
                });
            
            return $commands;
        });

        return self::$commandsCache;
    }

    public static function clearCache(): void
    {
        self::$commandsCache = null;
        Cache::forget('command_registry');
    }

    public static function isPhpCommand(string $commandName): bool
    {
        $commands = self::loadCommands();
        return array_key_exists($commandName, $commands);
    }

    public static function getPhpCommand(string $commandName): string
    {
        $commands = self::loadCommands();
        
        if (! isset($commands[$commandName])) {
            throw new \InvalidArgumentException("PHP Command not recognized: {$commandName}");
        }

        return $commands[$commandName]['handler_class'];
    }
    
    public static function getCommand(string $commandName): ?Command
    {
        $commands = self::loadCommands();
        return $commands[$commandName]['command'] ?? null;
    }

    public static function getAllPhpCommands(): array
    {
        $commands = self::loadCommands();
        return array_column($commands, 'handler_class');
    }
    
    public static function getCommandsForInterface(string $interface): array
    {
        $commands = Command::active()->with('type');
        
        return match($interface) {
            'slash', 'web' => $commands->where('available_in_slash', true)->get(),
            'cli' => $commands->where('available_in_cli', true)->get(),
            'mcp' => $commands->where('available_in_mcp', true)->get(),
            default => $commands->get(),
        };
    }

    public static function getAllCommandsWithHelp(): array
    {
        return Command::active()
            ->availableInSlash()
            ->with('type')
            ->get()
            ->map(function ($command) {
                $className = $command->handler_class;
                
                return [
                    'slug' => ltrim($command->command, '/'),
                    'name' => $command->name,
                    'description' => $command->description,
                    'usage' => class_exists($className) && method_exists($className, 'getUsage') 
                        ? $className::getUsage() 
                        : "/{$command->command}",
                    'category' => $command->category,
                    'aliases' => [],
                    'command' => $command,
                    'type' => $command->type,
                ];
            })
            ->toArray();
    }

    public static function getCommandsByCategory(?string $category = null): array
    {
        if ($category) {
            return Command::active()
                ->availableInSlash()
                ->where('category', $category)
                ->with('type')
                ->get()
                ->map(function ($command) {
                    return [
                        'slug' => ltrim($command->command, '/'),
                        'name' => $command->name,
                        'description' => $command->description,
                        'category' => $command->category,
                        'command' => $command,
                        'type' => $command->type,
                    ];
                })
                ->toArray();
        }

        return self::getAllCommandsWithHelp();
    }

    public static function find(string $commandName): string
    {
        logger('COMMAND_REGISTRY_LOOKUP', ['commandName' => $commandName]);
        return self::getPhpCommand($commandName);
    }

    public static function all(): array
    {
        $commands = self::loadCommands();
        return array_keys($commands);
    }
    
    public static function getAllCommands(): array
    {
        return Command::active()->with('type')->get()->all();
    }
}
