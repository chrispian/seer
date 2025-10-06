<?php

namespace App\Commands;

use App\Tools\Orchestration\SprintDetailTool;
use Laravel\Mcp\Request;

class SprintDetailCommand extends BaseCommand
{
    protected ?string $argument = null;

    public function __construct(?string $argument = null)
    {
        $this->argument = $argument;
    }

    public function handle(): array
    {
        $sprintCode = $this->getSprintCode();
        
        if (!$sprintCode) {
            return [
                'type' => 'error',
                'component' => null,
                'message' => 'Please provide a sprint code. Usage: /sprint-detail SPRINT-43 or /sprint-detail 43'
            ];
        }
        
        if (is_numeric($sprintCode)) {
            $sprintCode = 'SPRINT-' . $sprintCode;
        }
        
        try {
            $tool = app(SprintDetailTool::class);
            $request = new Request([
                'sprint' => $sprintCode,
                'include_tasks' => true,
                'tasks_limit' => 25
            ], 'command-session');
            
            $response = $tool->handle($request);
            $content = $response->content();
            $data = json_decode((string) $content, true);
            
            if (!$data || (isset($data['error']) && $data['error'])) {
                throw new \Exception($data['error'] ?? 'Sprint not found');
            }
            
            return [
                'type' => 'sprint',
                'component' => 'SprintDetailModal',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "Sprint '{$sprintCode}' not found. Use /sprints to see available sprints.\n\nError: " . $e->getMessage()
            ];
        }
    }
    
    private function getSprintCode(): ?string
    {
        return $this->argument ? trim($this->argument) : null;
    }
    
    public static function getName(): string
    {
        return 'Sprint Detail';
    }
    
    public static function getDescription(): string
    {
        return 'Show detailed information about a specific sprint';
    }
    
    public static function getUsage(): string
    {
        return '/sprint-detail [sprint-code]';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
