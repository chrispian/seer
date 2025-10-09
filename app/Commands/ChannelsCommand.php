<?php

namespace App\Commands;

class ChannelsCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get available channels/communication options
        $channels = $this->getChannels();

        return [
            'type' => 'channel',
            'component' => 'ChannelListModal',
            'data' => $channels,
        ];
    }

    private function getChannels(): array
    {
        // For now, return sample channels
        // This could be extended to read from a Channel model or configuration
        return [
            [
                'id' => 'general',
                'name' => 'General',
                'description' => 'General discussion channel',
                'type' => 'public',
                'member_count' => 0,
                'is_active' => true,
                'created_at' => now()->toISOString(),
            ],
            [
                'id' => 'development',
                'name' => 'Development',
                'description' => 'Development and technical discussions',
                'type' => 'public',
                'member_count' => 0,
                'is_active' => true,
                'created_at' => now()->toISOString(),
            ],
            [
                'id' => 'announcements',
                'name' => 'Announcements',
                'description' => 'Important announcements and updates',
                'type' => 'read-only',
                'member_count' => 0,
                'is_active' => true,
                'created_at' => now()->toISOString(),
            ],
        ];
    }

    public static function getName(): string
    {
        return 'Channels List';
    }

    public static function getDescription(): string
    {
        return 'List all available communication channels';
    }

    public static function getUsage(): string
    {
        return '/channels';
    }

    public static function getCategory(): string
    {
        return 'Communication';
    }
}
