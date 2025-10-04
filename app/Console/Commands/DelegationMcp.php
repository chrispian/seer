<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DelegationMcp extends Command
{
    protected $signature = 'delegation:mcp';
    protected $description = 'MCP server for delegation system sprint and agent management';

    public function handle()
    {
        $this->info('Starting Delegation MCP Server...');
        
        while (true) {
            $input = trim(fgets(STDIN));
            
            if (empty($input)) {
                continue;
            }
            
            try {
                $request = json_decode($input, true);
                $response = $this->handleRequest($request);
                echo json_encode($response) . "\n";
            } catch (\Exception $e) {
                $error = [
                    'error' => [
                        'code' => -1,
                        'message' => $e->getMessage()
                    ]
                ];
                echo json_encode($error) . "\n";
            }
        }
    }
    
    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        
        return match ($method) {
            'sprint/status' => $this->getSprintStatus(),
            'sprint/analyze' => $this->analyzeSprint($params['sprint'] ?? null),
            'agent/create' => $this->createAgent($params['role'] ?? null, $params['name'] ?? null),
            'agent/assign' => $this->assignAgent($params['agent'] ?? null, $params['task'] ?? null),
            'task/analyze' => $this->analyzeTask($params['task'] ?? null),
            'worktree/setup' => $this->setupWorktree($params['sprint'] ?? null),
            default => ['error' => 'Unknown method: ' . $method]
        };
    }
    
    private function getSprintStatus(): array
    {
        $statusFile = base_path('delegation/SPRINT_STATUS.md');
        
        if (!File::exists($statusFile)) {
            return ['error' => 'Sprint status file not found'];
        }
        
        $content = File::get($statusFile);
        
        // Parse current sprint info from the markdown
        preg_match('/Active Sprint: Sprint (\d+)/', $content, $matches);
        $currentSprint = $matches[1] ?? 'Unknown';
        
        // Count task statuses
        $todo = substr_count($content, '`todo`');
        $inProgress = substr_count($content, '`in-progress`');
        $review = substr_count($content, '`review`');
        $done = substr_count($content, '`done`');
        
        return [
            'result' => [
                'current_sprint' => $currentSprint,
                'tasks' => [
                    'todo' => $todo,
                    'in_progress' => $inProgress,
                    'review' => $review,
                    'done' => $done,
                    'total' => $todo + $inProgress + $review + $done
                ],
                'status_file' => $statusFile,
                'last_updated' => date('Y-m-d H:i:s', filemtime($statusFile))
            ]
        ];
    }
    
    private function analyzeSprint(?string $sprintNumber): array
    {
        if (!$sprintNumber) {
            return ['error' => 'Sprint number required'];
        }
        
        $sprintDir = base_path("delegation/sprint-{$sprintNumber}");
        
        if (!File::isDirectory($sprintDir)) {
            return ['error' => "Sprint {$sprintNumber} directory not found"];
        }
        
        $tasks = [];
        $directories = File::directories($sprintDir);
        
        foreach ($directories as $taskDir) {
            $taskId = basename($taskDir);
            $agentFile = $taskDir . '/AGENT.md';
            $planFile = $taskDir . '/PLAN.md';
            
            if (File::exists($agentFile) && File::exists($planFile)) {
                $agentContent = File::get($agentFile);
                $planContent = File::get($planFile);
                
                // Extract estimated hours from plan
                preg_match('/(\d+-\d+)\s*hours?/', $planContent, $timeMatches);
                $estimate = $timeMatches[1] ?? 'Unknown';
                
                // Extract agent type from agent file
                preg_match('/\*\*Type\*\*:\s*([^\n]+)/', $agentContent, $typeMatches);
                $agentType = trim($typeMatches[1] ?? 'Unknown');
                
                $tasks[] = [
                    'id' => $taskId,
                    'agent_type' => $agentType,
                    'estimate' => $estimate,
                    'ready' => File::exists($taskDir . '/TODO.md') && File::exists($taskDir . '/CONTEXT.md')
                ];
            }
        }
        
        return [
            'result' => [
                'sprint' => $sprintNumber,
                'task_count' => count($tasks),
                'tasks' => $tasks,
                'sprint_dir' => $sprintDir
            ]
        ];
    }
    
    private function createAgent(?string $role, ?string $name): array
    {
        if (!$role || !$name) {
            return ['error' => 'Role and name required'];
        }
        
        $templateFile = base_path("delegation/agents/templates/{$role}.md");
        
        if (!File::exists($templateFile)) {
            return ['error' => "Template for role '{$role}' not found"];
        }
        
        $template = File::get($templateFile);
        $agentId = strtolower($name) . '-' . strtolower(str_replace('-', '', $role)) . '-001';
        $agentFile = base_path("delegation/agents/active/{$agentId}.md");
        
        // Customize template with agent context
        $customizedAgent = str_replace(
            ['{SPECIALIZATION_CONTEXT}', '{AGENT_MISSION}'],
            ["Specialized agent: {$name}", "Agent mission will be defined upon task assignment"],
            $template
        );
        
        File::put($agentFile, $customizedAgent);
        
        return [
            'result' => [
                'agent_id' => $agentId,
                'role' => $role,
                'name' => $name,
                'file' => $agentFile,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    private function assignAgent(?string $agentId, ?string $taskId): array
    {
        if (!$agentId || !$taskId) {
            return ['error' => 'Agent ID and task ID required'];
        }
        
        $agentFile = base_path("delegation/agents/active/{$agentId}.md");
        
        if (!File::exists($agentFile)) {
            return ['error' => "Agent '{$agentId}' not found"];
        }
        
        // Find sprint number from task ID
        preg_match('/^[A-Z]+-(\d+)-/', $taskId, $matches);
        $sprintNumber = $matches[1] ?? null;
        
        if (!$sprintNumber) {
            return ['error' => 'Could not determine sprint number from task ID'];
        }
        
        $taskDir = base_path("delegation/sprint-{$sprintNumber}/{$taskId}");
        
        if (!File::isDirectory($taskDir)) {
            return ['error' => "Task '{$taskId}' not found"];
        }
        
        // Update agent file with assignment
        $agentContent = File::get($agentFile);
        $assignmentInfo = "\n\n## Current Assignment\n**Task**: {$taskId}\n**Assigned**: " . date('Y-m-d H:i:s') . "\n**Task Directory**: {$taskDir}";
        
        File::put($agentFile, $agentContent . $assignmentInfo);
        
        return [
            'result' => [
                'agent_id' => $agentId,
                'task_id' => $taskId,
                'task_directory' => $taskDir,
                'assigned_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    private function analyzeTask(?string $taskId): array
    {
        if (!$taskId) {
            return ['error' => 'Task ID required'];
        }
        
        // Find sprint number from task ID
        preg_match('/^[A-Z]+-(\d+)-/', $taskId, $matches);
        $sprintNumber = $matches[1] ?? null;
        
        if (!$sprintNumber) {
            return ['error' => 'Could not determine sprint number from task ID'];
        }
        
        $taskDir = base_path("delegation/sprint-{$sprintNumber}/{$taskId}");
        
        if (!File::isDirectory($taskDir)) {
            return ['error' => "Task '{$taskId}' not found"];
        }
        
        $files = ['AGENT.md', 'CONTEXT.md', 'PLAN.md', 'TODO.md'];
        $analysis = ['task_id' => $taskId, 'files' => []];
        
        foreach ($files as $file) {
            $filePath = $taskDir . '/' . $file;
            if (File::exists($filePath)) {
                $content = File::get($filePath);
                $analysis['files'][$file] = [
                    'exists' => true,
                    'size' => strlen($content),
                    'lines' => substr_count($content, "\n") + 1
                ];
            } else {
                $analysis['files'][$file] = ['exists' => false];
            }
        }
        
        $analysis['readiness'] = array_reduce($files, function($ready, $file) use ($analysis) {
            return $ready && $analysis['files'][$file]['exists'];
        }, true);
        
        return ['result' => $analysis];
    }
    
    private function setupWorktree(?string $sprintNumber): array
    {
        if (!$sprintNumber) {
            return ['error' => 'Sprint number required'];
        }
        
        $baseDir = dirname(base_path());
        $projectName = basename(base_path());
        
        $worktrees = [
            'backend' => "{$baseDir}/{$projectName}-backend-sprint{$sprintNumber}",
            'frontend' => "{$baseDir}/{$projectName}-frontend-sprint{$sprintNumber}",
            'integration' => "{$baseDir}/{$projectName}-integration-sprint{$sprintNumber}"
        ];
        
        $results = [];
        
        foreach ($worktrees as $type => $path) {
            $branchName = "sprint-{$sprintNumber}/{$type}";
            
            // Create branch if it doesn't exist
            exec("cd " . base_path() . " && git checkout -b {$branchName} 2>/dev/null", $output, $exitCode);
            
            // Create worktree
            exec("cd " . base_path() . " && git worktree add {$path} {$branchName} 2>&1", $output, $exitCode);
            
            $results[$type] = [
                'path' => $path,
                'branch' => $branchName,
                'success' => $exitCode === 0,
                'output' => implode("\n", $output)
            ];
        }
        
        return [
            'result' => [
                'sprint' => $sprintNumber,
                'worktrees' => $results,
                'setup_complete' => array_reduce($results, function($success, $result) {
                    return $success && $result['success'];
                }, true)
            ]
        ];
    }
}