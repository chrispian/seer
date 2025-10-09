<?php

namespace App\Commands;

abstract class BaseCommand
{
    /**
     * Handle the command execution
     */
    abstract public function handle(): array;

    /**
     * Get help information for this command
     */
    public function getHelp(): array
    {
        return [
            'name' => static::getName(),
            'description' => static::getDescription(),
            'usage' => static::getUsage(),
            'category' => static::getCategory(),
        ];
    }

    /**
     * Get command name (override in subclasses)
     */
    public static function getName(): string
    {
        return 'Unknown Command';
    }

    /**
     * Get command description (override in subclasses)
     */
    public static function getDescription(): string
    {
        return 'No description available';
    }

    /**
     * Get command usage (override in subclasses)
     */
    public static function getUsage(): string
    {
        return '/command';
    }

    /**
     * Get command category (override in subclasses)
     */
    public static function getCategory(): string
    {
        return 'General';
    }
}
