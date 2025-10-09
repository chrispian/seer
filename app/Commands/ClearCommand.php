<?php

namespace App\Commands;

class ClearCommand extends BaseCommand
{
    public function handle(): array
    {
        // Clear command should reset chat and provide success feedback
        return [
            'type' => 'clear',
            'component' => null,
            'data' => null,
            'message' => 'Chat cleared successfully. The conversation history has been reset.',
            'shouldResetChat' => true,
        ];
    }

    public static function getName(): string
    {
        return 'Clear Chat';
    }

    public static function getDescription(): string
    {
        return 'Clear the current chat conversation history';
    }

    public static function getUsage(): string
    {
        return '/clear';
    }

    public static function getCategory(): string
    {
        return 'Utility';
    }
}
