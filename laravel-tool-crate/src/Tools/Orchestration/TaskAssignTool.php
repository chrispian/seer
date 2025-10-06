<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TaskAssignTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration.tasks.assign';
    protected string $title = 'Assign orchestration task';
    protected string $description = 'Create an assignment linking a work item to an agent and update delegation status.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->required()->description('Task UUID or delegation task code'),
            'agent' => $schema->string()->required()->description('Agent UUID or slug'),
            'status' => $schema->string()->enum([
                'assigned', 'in_progress', 'blocked', 'completed', 'cancelled', 'unassigned',
            ])->default('assigned'),
            'assignment_status' => $schema->string()->enum([
                'assigned', 'in_progress', 'blocked', 'completed', 'cancelled', 'unassigned',
            ])->description('Override assignment state; defaults to status value'),
            'note' => $schema->string()->description('Optional note stored with assignment'),
            'context' => $schema->object()->additionalProperties(true)->description('Additional context payload'),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task' => ['required', 'string'],
            'agent' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'assignment_status' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'context' => ['nullable', 'array'],
        ]);

        $service = ModelResolver::resolveService('task_service', 'App\\Services\\TaskOrchestrationService');

        $result = $service->assignAgent($validated['task'], $validated['agent'], Arr::only($validated, [
            'status', 'assignment_status', 'note', 'context',
        ]));

        $detail = $service->detail($result['task'], [
            'assignments_limit' => 10,
            'include_history' => true,
        ]);

        return Response::json([
            'assignment' => $detail['current_assignment'],
            'task' => $detail['task'],
            'assignments' => $detail['assignments'],
        ]);
    }

    public static function summaryName(): string
    {
        return 'orchestration.tasks.assign';
    }

    public static function summaryTitle(): string
    {
        return 'Assign orchestration task';
    }

    public static function summaryDescription(): string
    {
        return 'Create a task assignment for an agent and set delegation status.';
    }

    public static function schemaSummary(): array
    {
        return [
            'task' => 'Task UUID or code',
            'agent' => 'Agent UUID or slug',
            'status' => 'delegation status (default assigned)',
            'note' => 'optional note stored in history',
        ];
    }
}
