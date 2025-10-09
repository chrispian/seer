<?php

namespace App\Commands;

class SetupCommand extends BaseCommand
{
    public function handle(): array
    {
        return [
            'type' => 'navigation',
            'component' => null,
            'data' => [
                'action' => 'navigate',
                'url' => '/setup/welcome'
            ],
            'message' => 'Opening Setup Wizard...'
        ];
    }

    public static function getName(): string
    {
        return 'Open Setup Wizard';
    }

    public static function getDescription(): string
    {
        return 'Initial setup and configuration';
    }

    public static function getUsage(): string
    {
        return '/setup';
    }

    public static function getCategory(): string
    {
        return 'System';
    }
}