<?php

namespace App\Commands;

class ComposeCommand extends BaseCommand
{
    public function handle(): array
    {
        return [
            'type' => 'message',
            'component' => null,
            'data' => null,
            'message' => 'Compose functionality coming soon. Use this to create new messages, fragments, or content.',
        ];
    }

    public static function getName(): string
    {
        return 'Compose';
    }

    public static function getDescription(): string
    {
        return 'Compose new messages, fragments, or content';
    }

    public static function getUsage(): string
    {
        return '/compose';
    }

    public static function getCategory(): string
    {
        return 'Content';
    }
}
