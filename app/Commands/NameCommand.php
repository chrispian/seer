<?php

namespace App\Commands;

class NameCommand extends BaseCommand
{
    public function handle(): array
    {
        // Name command for setting session/context names
        return [
            'type' => 'message',
            'component' => null,
            'data' => null,
            'message' => 'Name command executed. Use this to set names for sessions or contexts.'
        ];
    }
    
    public static function getName(): string
    {
        return 'Set Name';
    }
    
    public static function getDescription(): string
    {
        return 'Set names for sessions, contexts, or other entities';
    }
    
    public static function getUsage(): string
    {
        return '/name [new_name]';
    }
    
    public static function getCategory(): string
    {
        return 'Utility';
    }
}