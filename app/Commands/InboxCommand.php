<?php

namespace App\Commands;

class InboxCommand extends BaseCommand
{
    public function handle(): array
    {
        return [
            'type' => 'message',
            'component' => null,
            'data' => null,
            'message' => 'Inbox functionality coming soon. Use this to manage incoming messages and notifications.'
        ];
    }
    
    public static function getName(): string
    {
        return 'Inbox';
    }
    
    public static function getDescription(): string
    {
        return 'Manage inbox messages and notifications';
    }
    
    public static function getUsage(): string
    {
        return '/inbox';
    }
    
    public static function getCategory(): string
    {
        return 'Communication';
    }
}