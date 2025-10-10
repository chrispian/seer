<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Tools\Orchestration\TaskDetailTool;
use Laravel\Mcp\Request;

class DetailCommand extends BaseCommand
{
    protected ?string $argument = null;

    public function __construct(?string $argument = null)
    {
        $this->argument = $argument;
    }

    public function handle(): array
    {
        $taskCode = $this->getTaskCode();

        if (! $taskCode) {
            return [
                'type' => 'error',
                'component' => null,
                'message' => 'Please provide a task code. Usage: /task-detail T-ART-02-CAS',
            ];
        }

        try {
            $tool = app(TaskDetailTool::class);
            $request = new Request([
                'task' => $taskCode,
                'include_history' => true,
                'assignments_limit' => 20,
            ], 'command-session');

            $response = $tool->handle($request);
            $content = $response->content();
            $data = json_decode((string) $content, true);

            return [
                'type' => 'task',
                'component' => 'TaskDetailModal',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "Task '{$taskCode}' not found. Use /tasks to see available tasks.",
            ];
        }
    }

    private function getTaskCode(): ?string
    {
        return $this->argument ? trim($this->argument) : null;
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
        return '/task-detail [task-code]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
