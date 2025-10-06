<?php

namespace App\Commands;

use App\Services\CommandRegistry;

class HelpCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get namespace filter from command arguments if any
        $namespace = $this->getNamespaceFilter();
        
        if ($namespace) {
            $commands = CommandRegistry::getCommandsByCategory($namespace);
            if (empty($commands)) {
                return [
                    'type' => 'help',
                    'component' => null,
                    'data' => null,
                    'message' => "**Invalid namespace: {$namespace}**\n\nAvailable namespaces: " . 
                                implode(', ', $this->getAvailableNamespaces())
                ];
            }
        } else {
            $commands = CommandRegistry::getAllCommandsWithHelp();
        }
        
        return [
            'type' => 'help',
            'component' => 'HelpModal',
            'data' => [
                'commands' => $commands,
                'categories' => $this->groupByCategory($commands),
                'namespace' => $namespace,
                'markdown_help' => $this->generateMarkdownHelp($commands, $namespace),
            ]
        ];
    }
    
    private function groupByCategory(array $commands): array
    {
        $categories = [];
        foreach ($commands as $command) {
            $category = $command['category'] ?? 'General';
            $categories[$category][] = $command;
        }
        return $categories;
    }
    
    private function getNamespaceFilter(): ?string
    {
        // TODO: Extract from command context/arguments
        // For now, return null to show all commands
        return null;
    }
    
    private function getAvailableNamespaces(): array
    {
        $commands = CommandRegistry::getAllCommandsWithHelp();
        $namespaces = array_unique(array_column($commands, 'category'));
        sort($namespaces);
        return $namespaces;
    }
    
    private function generateMarkdownHelp(array $commands, ?string $namespace): string
    {
        $title = $namespace ? "# {$namespace} Commands" : "# Available Commands";
        $help = "{$title}\n\n";
        
        $categories = $this->groupByCategory($commands);
        
        foreach ($categories as $category => $categoryCommands) {
            if (!$namespace) {
                $help .= "## {$category}\n\n";
            }
            
            foreach ($categoryCommands as $command) {
                $help .= "### `{$command['usage']}`";
                
                // Add aliases if they exist
                if (!empty($command['aliases'])) {
                    $aliasesStr = implode('`, `/', $command['aliases']);
                    $help .= " (aliases: `/{$aliasesStr}`)";
                }
                
                $help .= "\n";
                $help .= "{$command['description']}\n\n";
                
                // Add example if this is a commonly used command
                if (in_array($command['slug'], ['search', 'tasks', 'help'])) {
                    $help .= "**Example:** `{$command['usage']}`\n\n";
                }
            }
        }
        
        if (!$namespace) {
            $help .= "---\n\n";
            $help .= "**Tips:**\n";
            $help .= "- Use `/help {category}` to see commands for a specific category\n";
            $help .= "- Most commands work with additional arguments\n";
            $help .= "- Try `/tasks`, `/agents`, or `/sprints` to get started\n\n";
        }
        
        return $help;
    }
    
    public static function getName(): string
    {
        return 'Help System';
    }
    
    public static function getDescription(): string
    {
        return 'Show help for all available commands';
    }
    
    public static function getUsage(): string
    {
        return '/help';
    }
    
    public static function getCategory(): string
    {
        return 'System';
    }
}