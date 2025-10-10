<?php

namespace App\Tools\Orchestration;

use App\Commands\Orchestration\Task\AssignCommand;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TaskAssignTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_tasks_assign';

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
            'context' => $schema->object()->description('Additional context payload'),
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

        $command = new AssignCommand([
            'task_code' => $validated['task'],
            'agent_slug' => $validated['agent'],
            'status' => $validated['status'] ?? 'assigned',
            'note' => $validated['note'] ?? null,
            'context' => $validated['context'] ?? null,
        ]);
        
        $command->setContext('mcp');
        $result = $command->handle();

        return Response::json($result);
    }

    public static function summaryName(): string
    {
        return 'orchestration_tasks_assign';
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
