<?php

namespace App\Commands;

class JoinCommand extends BaseCommand
{
    public function handle(): array
    {
        // Return a simple success message for join command
        // This is a placeholder - could be extended for actual channel joining
        return [
            'type' => 'message',
            'component' => null,
            'data' => null,
            'message' => 'Join command executed. Use /channels to see available channels.',
        ];
    }

    public static function getName(): string
    {
        return 'Join Channel';
    }

    public static function getDescription(): string
    {
        return 'Join a communication channel or room';
    }

    public static function getUsage(): string
    {
        return '/join [channel]';
    }

    public static function getCategory(): string
    {
        return 'Communication';
    }
}
