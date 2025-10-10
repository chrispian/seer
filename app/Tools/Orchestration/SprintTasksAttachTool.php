<?php

namespace App\Tools\Orchestration;

use App\Commands\Orchestration\Sprint\AttachTasksCommand;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintTasksAttachTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_sprints_attach_tasks';

    protected string $title = 'Attach tasks to sprint';

    protected string $description = 'Associate existing work items with a sprint, updating metadata and sprint ordering.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint' => $schema->string()->required()->description('Sprint code or UUID'),
            'tasks' => $schema->array()->items($schema->string())->min(1)->required()->description('List of task UUIDs or task codes'),
            'tasks_limit' => $schema->integer()->min(1)->max(25)->default(10),
            'include_assignments' => $schema->boolean()->default(false),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'sprint' => ['required', 'string'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*' => ['string'],
            'tasks_limit' => ['nullable', 'integer'],
            'include_assignments' => ['nullable', 'boolean'],
        ]);

        $command = new AttachTasksCommand([
            'sprint_code' => $validated['sprint'],
            'task_codes' => $validated['tasks'],
            'include_tasks' => true,
            'tasks_limit' => $validated['tasks_limit'] ?? 10,
            'include_assignments' => $validated['include_assignments'] ?? false,
        ]);
        
        $command->setContext('mcp');
        $result = $command->handle();

        return Response::json($result);
    }

    public static function summaryName(): string
    {
        return 'orchestration_sprints_attach_tasks';
    }

    public static function summaryTitle(): string
    {
        return 'Attach tasks to sprint';
    }

    public static function summaryDescription(): string
    {
        return 'Add existing work items to a sprint and update metadata.';
    }

    public static function schemaSummary(): array
    {
        return [
            'sprint' => 'Sprint code or UUID',
            'tasks[]' => 'Task codes/UUIDs to attach',
        ];
    }
}
