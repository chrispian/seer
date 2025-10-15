<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Tools\Orchestration\TaskDetailTool;
use Laravel\Mcp\Request;

class DetailCommand extends BaseCommand
{
    protected ?string $identifier = null;

    public function __construct(array $options = [])
    {
        // Support 'code', 'id', or first positional argument
        $this->identifier = $options['code'] ?? $options['id'] ?? $options[0] ?? null;
    }

    public function handle(): array
    {
        $taskIdentifier = $this->identifier;

        if (! $taskIdentifier) {
            return $this->respond(
                ['error' => 'Please provide a task code or ID. Usage: /orch-task 123 or /orch-task TASK-001'],
                null
            );
        }

        try {
            $tool = app(TaskDetailTool::class);
            $request = new Request([
                'task' => $taskIdentifier,
                'include_history' => true,
                'assignments_limit' => 20,
            ], 'command-session');

            $response = $tool->handle($request);
            $content = $response->content();
            $data = json_decode((string) $content, true);

            return $this->respond($data, 'TaskDetailModal');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Check for similar matches
            if (property_exists($e, 'similarMatches') && !empty($e->similarMatches)) {
                $message = "Task '{$taskIdentifier}' not found. Did you mean one of these?\n\n";
                foreach ($e->similarMatches as $match) {
                    $message .= "- {$match['task_code']}: {$match['task_name']} (ID: {$match['id']})\n";
                }
                $message .= "\nUse: /orch-task [id|task-code]";
                
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => $message,
                ];
            }
            
            return [
                'type' => 'message',
                'component' => null,
                'message' => "Task '{$taskIdentifier}' not found. Use /orch-tasks to see available tasks.",
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "Error loading task: " . $e->getMessage(),
            ];
        }
    }

    public static function getName(): string
    {
        return 'Task Detail';
    }

    public static function getDescription(): string
    {
        return 'Show detailed information about a specific task';
    }

    public static function getUsage(): string
    {
        return '/orch-task [id|task-code]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
