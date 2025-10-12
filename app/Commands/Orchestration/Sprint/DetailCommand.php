<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Tools\Orchestration\SprintDetailTool;
use Laravel\Mcp\Request;

class DetailCommand extends BaseCommand
{
    protected ?string $code = null;

    public function __construct(array $options = [])
    {
        // Support both 'code' parameter and first positional argument
        $this->code = $options['code'] ?? $options[0] ?? null;
    }

    public function handle(): array
    {
        $sprintCode = $this->code;

        if (! $sprintCode) {
            return $this->respond(
                ['error' => 'Please provide a sprint code. Usage: /sprint-detail SPRINT-43 or /sprint-detail 43'],
                null
            );
        }

        if (is_numeric($sprintCode)) {
            $sprintCode = 'SPRINT-'.$sprintCode;
        }

        try {
            $tool = app(SprintDetailTool::class);
            $request = new Request([
                'sprint' => $sprintCode,
                'include_tasks' => true,
                'tasks_limit' => 25,
            ], 'command-session');

            $response = $tool->handle($request);
            $content = $response->content();
            $rawData = json_decode((string) $content, true);

            if (! $rawData || (isset($rawData['error']) && $rawData['error'])) {
                throw new \Exception($rawData['error'] ?? 'Sprint not found');
            }

            $sprint = $rawData['sprint'] ?? null;
            if (! $sprint) {
                throw new \Exception('Sprint data not found');
            }

            $tasks = $sprint['tasks'] ?? [];
            $stats = $sprint['stats'] ?? ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'todo' => 0, 'backlog' => 0];
            unset($sprint['tasks'], $sprint['stats']);

            return $this->respond([
                'sprint' => $sprint,
                'tasks' => $tasks,
                'stats' => $stats,
            ], 'SprintDetailModal');
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "Sprint '{$sprintCode}' not found. Use /sprints to see available sprints.\n\nError: ".$e->getMessage(),
            ];
        }
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
