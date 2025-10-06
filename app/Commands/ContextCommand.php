<?php

namespace App\Commands;

class ContextCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get current context information
        $context = $this->getContextInfo();
        
        return [
            'type' => 'context',
            'component' => null,
            'data' => null,
            'message' => "**Current Context:**\n\n" . $context
        ];
    }
    
    private function getContextInfo(): string
    {
        return "Vault: Current Vault\nProject: Current Project\nSession: Active Session\n\nContext management commands will be available here.";
    }
    
    public static function getName(): string
    {
        return 'Context Info';
    }
    
    public static function getDescription(): string
    {
        return 'Show current context information';
    }
    
    public static function getUsage(): string
    {
        return '/context';
    }
    
    public static function getCategory(): string
    {
        return 'Navigation';
    }
}