<?php

namespace App\Commands;

class RoutingCommand extends BaseCommand
{
    public function handle(): array
    {
        // Routing information command
        $routes = $this->getRoutingInfo();

        return [
            'type' => 'routing',
            'component' => 'RoutingInfoModal',
            'data' => $routes,
        ];
    }

    private function getRoutingInfo(): array
    {
        // Return basic routing information
        return [
            'current_context' => [
                'vault' => 'Current Vault',
                'project' => 'Current Project',
                'session' => 'Current Session',
            ],
            'available_routes' => [
                '/help' => 'Show command help',
                '/tasks' => 'List tasks',
                '/agents' => 'List agents',
                '/sprints' => 'List sprints',
                '/session' => 'List sessions',
                '/bookmark' => 'List bookmarks',
                '/search' => 'Search content',
                '/recall' => 'Recall fragments',
                '/clear' => 'Clear chat',
            ],
            'routing_status' => 'active',
            'timestamp' => now()->toISOString(),
        ];
    }

    public static function getName(): string
    {
        return 'Routing Info';
    }

    public static function getDescription(): string
    {
        return 'Display routing and navigation information';
    }

    public static function getUsage(): string
    {
        return '/routing';
    }

    public static function getCategory(): string
    {
        return 'System';
    }
}
