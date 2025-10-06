<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use HollisLabs\ToolCrate\Tools\Orchestration\Concerns\NormalisesFilters;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintsListTool extends Tool implements SummarizesTool
{
    use NormalisesFilters;

    protected string $name = 'orchestration.sprints.list';
    protected string $title = 'Summarise orchestration sprints';
    protected string $description = 'Return sprints with progress stats and optional task details.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->array()->items($schema->string()),
            'limit' => $schema->integer()->minimum(1)->maximum(50)->default(10),
            'details' => $schema->boolean()->default(false),
            'tasks_limit' => $schema->integer()->minimum(1)->maximum(20)->default(5),
        ];
    }

    public function handle(Request $request): Response
    {
        $sprintModel = ModelResolver::resolve('sprint_model', class_exists('App\\Models\\Sprint') ? 'App\\Models\\Sprint' : null);
        $service = ModelResolver::resolveService('sprint_service', 'App\\Services\\SprintOrchestrationService');

        $query = $sprintModel::query()->orderByDesc('created_at')->orderByDesc('updated_at');

        if ($request->get('code')) {
            $codes = $this->normaliseCodes((array) $request->get('code'));
            if ($codes) {
                $query->whereIn('code', $codes);
            }
        }

        $limit = $this->normalisePositiveInt($request->get('limit'), 10) ?? 10;
        $query->limit($limit);

        $sprints = $query->get();

        $withTasks = (bool) $request->get('details', false);
        $tasksLimit = $withTasks ? $this->normalisePositiveInt($request->get('tasks_limit'), 5) ?? 5 : 0;

        $data = $sprints->map(fn ($sprint) => $service->summarise($sprint, $withTasks, $tasksLimit))->values();

        return Response::json([
            'data' => $data->all(),
            'meta' => [
                'count' => $data->count(),
            ],
        ]);
    }

    public static function summaryName(): string
    {
        return 'orchestration.sprints.list';
    }

    public static function summaryTitle(): string
    {
        return 'Summarise orchestration sprints';
    }

    public static function summaryDescription(): string
    {
        return 'Sprint progress with optional recent tasks.';
    }

    public static function schemaSummary(): array
    {
        return [
            'code[]' => 'SPRINT-XX filters',
            'limit' => 'defaults to 10',
            'details' => 'include recent tasks',
            'tasks_limit' => 'max tasks when details=true',
        ];
    }
}
