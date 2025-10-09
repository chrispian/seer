<?php

namespace App\Commands;

class ScheduleListCommand extends BaseCommand
{
    public function handle(): array
    {
        // TODO: Implement actual scheduled task listing
        // This would query the database for scheduled commands/jobs

        return [
            'type' => 'message',
            'component' => null,
            'data' => null,
            'message' => '📅 Scheduled Tasks List\n\nScheduled task listing is not yet implemented in PHP commands.\nThis feature is available via YAML command system.\n\nUse /schedule-list-yaml or implement job scheduling integration.',
        ];
    }

    public static function getName(): string
    {
        return 'List Scheduled Tasks';
    }

    public static function getDescription(): string
    {
        return 'List scheduled tasks';
    }

    public static function getUsage(): string
    {
        return '/schedule-list';
    }

    public static function getCategory(): string
    {
        return 'Scheduling';
    }
}
