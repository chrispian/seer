<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintTasksAttachTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration.sprints.attach_tasks';
    protected string $title = 'Attach tasks to sprint';
    protected string $description = 'Associate existing work items with a sprint, updating metadata and sprint ordering.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint' => $schema->string()->required()->description('Sprint code or UUID'),
            'tasks' => $schema->array()->items($schema->string())->minItems(1)->required()->description('List of task UUIDs or task codes'),
            'tasks_limit' => $schema->integer()->minimum(1)->maximum(25)->default(10),
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

        $service = ModelResolver::resolveService('sprint_service', 'App\\Services\\SprintOrchestrationService');

        $payload = $service->attachTasks(
            $validated['sprint'],
            $validated['tasks'],
            [
                'tasks_limit' => $validated['tasks_limit'] ?? 10,
                'include_tasks' => true,
                'include_assignments' => $validated['include_assignments'] ?? false,
            ]
        );

        return Response::json($payload);
    }

    public static function summaryName(): string
    {
        return 'orchestration.sprints.attach_tasks';
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
