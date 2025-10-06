<?php

namespace App\Commands;

use App\Tools\Orchestration\AgentDetailTool;
use Laravel\Mcp\Request;

class AgentDetailCommand extends BaseCommand
{
    protected ?string $argument = null;

    public function __construct(?string $argument = null)
    {
        $this->argument = $argument;
    }

    public function handle(): array
    {
        $agentId = $this->getAgentId();
        
        if (!$agentId) {
            return [
                'type' => 'error',
                'component' => null,
                'message' => 'Please provide an agent slug or name. Usage: /agent-detail backend-engineer'
            ];
        }
        
        try {
            $tool = app(AgentDetailTool::class);
            $request = new Request([
                'agent' => $agentId,
                'include_history' => true,
                'assignments_limit' => 20
            ], 'command-session');
            
            $response = $tool->handle($request);
            $content = $response->content();
            $data = json_decode((string) $content, true);
            
            $agent = $data['agent'] ?? null;
            if (!$agent) {
                throw new \Exception('Agent data not found');
            }
            
            $stats = $data['stats'] ?? [];
            $assignments = $data['recent_assignments'] ?? [];
            
            $message = "**{$agent['name']}** (`{$agent['slug']}`)\n\n";
            $message .= "**Type:** {$agent['type']}\n";
            $message .= "**Mode:** {$agent['mode']}\n";
            $message .= "**Status:** {$agent['status']}\n\n";
            
            if (!empty($agent['capabilities'])) {
                $message .= "**Capabilities:**\n";
                foreach ($agent['capabilities'] as $capability) {
                    $message .= "- {$capability}\n";
                }
                $message .= "\n";
            }
            
            if (!empty($stats)) {
                $message .= "**Assignment Stats:**\n";
                $message .= "- Total: {$stats['assignments_total']}\n";
                $message .= "- Active: {$stats['assignments_active']}\n";
                $message .= "- Completed: {$stats['assignments_completed']}\n\n";
            }
            
            if (!empty($assignments)) {
                $message .= "**Recent Assignments:**\n";
                foreach (array_slice($assignments, 0, 5) as $assignment) {
                    $message .= "- {$assignment['work_item_code']} ({$assignment['status']})\n";
                }
            }
            
            return [
                'type' => 'message',
                'component' => null,
                'message' => $message
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "Agent '{$agentId}' not found. Use /agents to see available agents."
            ];
        }
    }
    
    private function getAgentId(): ?string
    {
        return $this->argument ? trim($this->argument) : null;
    }
    
    public static function getName(): string
    {
        return 'Agent Detail';
    }
    
    public static function getDescription(): string
    {
        return 'Show detailed information about a specific agent';
    }
    
    public static function getUsage(): string
    {
        return '/agent-detail [agent-slug]';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
