<?php

namespace App\Tools\Orchestration;

use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TaskDetailTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_tasks_detail';
    protected string $title = 'Show orchestration task detail';
    protected string $description = 'Return delegation-aware details for a work item, including assignments and history.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->required()->description('Task UUID or delegation task code'),
            'assignments_limit' => $schema->integer()->min(1)->max(20)->default(10),
            'include_history' => $schema->boolean()->default(true),
        ];
    }

    public function handle(Request $request): Response
    {
        $taskIdentifier = (string) $request->get('task');
        $assignmentsLimit = (int) $request->get('assignments_limit', 10);
        $includeHistory = (bool) $request->get('include_history', true);

        $service = ModelResolver::resolveService('task_service', 'App\\Services\\TaskOrchestrationService');
        $payload = $service->detail($taskIdentifier, [
            'assignments_limit' => $assignmentsLimit,
            'include_history' => $includeHistory,
        ]);

        return Response::json($payload);
    }

    public static function summaryName(): string
    {
        return 'orchestration_tasks_detail';
    }

    public static function summaryTitle(): string
    {
        return 'Show orchestration task detail';
    }

    public static function summaryDescription(): string
    {
        return 'Task metadata, history, and assignment timeline.';
    }

    public static function schemaSummary(): array
    {
        return [
            'task' => 'Task UUID or task code',
            'assignments_limit' => 'limit assignments (default 10)',
            'include_history' => 'toggle delegation history',
        ];
    }
}
