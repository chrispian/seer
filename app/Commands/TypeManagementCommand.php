<?php

namespace App\Commands;

class TypeManagementCommand extends BaseCommand
{
    public function handle(): array
    {
        return [
            'type' => 'type_management',
            'component' => 'TypeManagementModal',
            'data' => []
        ];
    }
    
    public static function getName(): string
    {
        return 'Type Management';
    }
    
    public static function getDescription(): string
    {
        return 'Manage fragment types - enable/disable types';
    }
    
    public static function getUsage(): string
    {
        return '/types';
    }
    
    public static function getCategory(): string
    {
        return 'Admin';
    }
}
