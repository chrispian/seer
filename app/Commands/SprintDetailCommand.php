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
            $rawData = json_decode((string) $content, true);
            
            if (!$rawData || (isset($rawData['error']) && $rawData['error'])) {
                throw new \Exception($rawData['error'] ?? 'Sprint not found');
            }
            
            $sprint = $rawData['sprint'] ?? null;
            if (!$sprint) {
                throw new \Exception('Sprint data not found');
            }
            
            $tasks = $sprint['tasks'] ?? [];
            $stats = $sprint['stats'] ?? ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'todo' => 0, 'backlog' => 0];
            unset($sprint['tasks'], $sprint['stats']);
            
            return [
                'type' => 'sprint',
                'component' => 'SprintDetailModal',
                'data' => [
                    'sprint' => $sprint,
                    'tasks' => $tasks,
                    'stats' => $stats
                ]
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
