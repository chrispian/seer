<?php

namespace App\Tools\Orchestration;

use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintDetailTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_sprints_detail';
    protected string $title = 'Show orchestration sprint detail';
    protected string $description = 'Return sprint metadata, stats, and recent tasks with delegation context.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint' => $schema->string()->required()->description('Sprint code (e.g. SPRINT-62) or UUID'),
            'tasks_limit' => $schema->integer()->min(1)->max(25)->default(10),
            'include_tasks' => $schema->boolean()->default(true),
            'include_assignments' => $schema->boolean()->default(false),
        ];
    }

    public function handle(Request $request): Response
    {
        $service = ModelResolver::resolveService('sprint_service', 'App\\Services\\SprintOrchestrationService');

        $payload = $service->detail($request->get('sprint'), [
            'tasks_limit' => (int) $request->get('tasks_limit', 10),
            'include_tasks' => (bool) $request->get('include_tasks', true),
            'include_assignments' => (bool) $request->get('include_assignments', false),
        ]);

        return Response::json($payload);
    }

    public static function summaryName(): string
    {
        return 'orchestration_sprints_detail';
    }

    public static function summaryTitle(): string
    {
        return 'Show orchestration sprint detail';
    }

    public static function summaryDescription(): string
    {
        return 'Sprint meta + stats + recent tasks.';
    }

    public static function schemaSummary(): array
    {
        return [
            'sprint' => 'Sprint code or UUID',
            'tasks_limit' => 'max tasks (default 10)',
        ];
    }
}
