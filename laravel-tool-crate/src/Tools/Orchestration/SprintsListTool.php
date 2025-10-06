<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use HollisLabs\ToolCrate\Tools\Orchestration\Concerns\NormalisesFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
        $workItemModel = ModelResolver::resolve('work_item_model', class_exists('App\\Models\\WorkItem') ? 'App\\Models\\WorkItem' : null);

        /** @var Builder $query */
        $query = $sprintModel::query()->orderByDesc('created_at')->orderByDesc('updated_at');

        if ($request->get('code')) {
            $codes = $this->normaliseCodes((array) $request->get('code'));
            if ($codes) {
                $query->whereIn('code', $codes);
            }
        }

        $limit = $this->normalisePositiveInt($request->get('limit'), 10) ?? 10;
        $query->limit($limit);

        /** @var Collection<int, \Illuminate\Database\Eloquent\Model> $sprints */
        $sprints = $query->get();

        $withTasks = (bool) $request->get('details', false);
        $tasksLimit = $withTasks ? $this->normalisePositiveInt($request->get('tasks_limit'), 5) ?? 5 : 0;

        $data = $sprints->map(function ($sprint) use ($workItemModel, $withTasks, $tasksLimit) {
            /** @var Builder $taskQuery */
            $taskQuery = $workItemModel::query()->where('metadata->sprint_code', $sprint->code);

            $total = (clone $taskQuery)->count();
            $completed = (clone $taskQuery)->where('delegation_status', 'completed')->count();
            $inProgress = (clone $taskQuery)->whereIn('delegation_status', ['assigned', 'in_progress'])->count();
            $blocked = (clone $taskQuery)->where('delegation_status', 'blocked')->count();
            $unassigned = (clone $taskQuery)->where('delegation_status', 'unassigned')->count();

            $tasks = [];

            if ($withTasks && $total > 0) {
                $tasks = (clone $taskQuery)
                    ->orderByDesc('created_at')
                    ->limit($tasksLimit)
                    ->get()
                    ->map(function ($item) {
                        $metadata = $item->metadata ?? [];
                        $context = $item->delegation_context ?? [];

                        return [
                            'task_code' => Arr::get($metadata, 'task_code'),
                            'status' => $item->status,
                            'delegation_status' => $item->delegation_status,
                            'agent_recommendation' => Arr::get($context, 'agent_recommendation'),
                            'estimate_text' => Arr::get($metadata, 'estimate_text'),
                        ];
                    })
                    ->values()
                    ->all();
            }

            $meta = $sprint->meta ?? [];

            return [
                'code' => $sprint->code,
                'title' => Arr::get($meta, 'title', $sprint->code),
                'priority' => Arr::get($meta, 'priority'),
                'estimate' => Arr::get($meta, 'estimate'),
                'notes' => Arr::get($meta, 'notes', []),
                'stats' => [
                    'total' => $total,
                    'completed' => $completed,
                    'in_progress' => $inProgress,
                    'blocked' => $blocked,
                    'unassigned' => $unassigned,
                ],
                'tasks' => $tasks,
                'updated_at' => $this->optionalIso($sprint->updated_at),
                'updated_human' => $this->optionalHuman($sprint->updated_at),
            ];
        });

        return Response::json([
            'data' => $data,
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
