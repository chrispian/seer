<?php

namespace App\Tools\Orchestration;

use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use App\Tools\Orchestration\Concerns\NormalisesFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TasksListTool extends Tool implements SummarizesTool
{
    use NormalisesFilters;

    protected string $name = 'orchestration_tasks_list';

    protected string $title = 'List orchestration tasks';

    protected string $description = 'Return work items with orchestration metadata and filters.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint' => $schema->array()->items($schema->string()),
            'delegation_status' => $schema->array()->items($schema->string()),
            'status' => $schema->array()->items($schema->string()),
            'agent' => $schema->string(),
            'search' => $schema->string(),
            'limit' => $schema->integer()->min(1)->max(100)->default(20),
        ];
    }

    public function handle(Request $request): Response
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = ModelResolver::resolve('work_item_model', class_exists('App\\Models\\WorkItem') ? 'App\\Models\\WorkItem' : null);

        /** @var Builder $query */
        $query = $model::query()->whereNotNull('metadata->task_code')->orderByDesc('created_at');

        if ($request->get('sprint')) {
            $codes = $this->normaliseCodes((array) $request->get('sprint'));
            if ($codes) {
                $query->whereIn('metadata->sprint_code', $codes);
            }
        }

        if ($request->get('delegation_status')) {
            $query->whereIn('delegation_status', $this->normaliseLowercaseArray($request->get('delegation_status')));
        }

        if ($request->get('status')) {
            $query->whereIn('status', $this->normaliseLowercaseArray($request->get('status')));
        }

        if ($request->get('agent')) {
            $query->where('delegation_context->agent_recommendation', trim((string) $request->get('agent')));
        }

        if ($request->get('search')) {
            $term = '%'.trim((string) $request->get('search')).'%';
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('metadata->task_code', 'like', $term)
                    ->orWhere('metadata->task_name', 'like', $term)
                    ->orWhere('metadata->description', 'like', $term);
            });
        }

        $limit = $this->normalisePositiveInt($request->get('limit'), 20) ?? 20;
        $query->limit($limit);

        /** @var Collection<int, \Illuminate\Database\Eloquent\Model> $tasks */
        $tasks = $query->get();

        $data = $tasks->map(function ($task) {
            $metadata = $task->metadata ?? [];
            $context = $task->delegation_context ?? [];

            return [
                'id' => $task->getKey(),
                'task_code' => Arr::get($metadata, 'task_code'),
                'task_name' => Arr::get($metadata, 'task_name'),
                'sprint_code' => Arr::get($metadata, 'sprint_code'),
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'agent_recommendation' => Arr::get($context, 'agent_recommendation'),
                'estimate_text' => Arr::get($metadata, 'estimate_text'),
                'todo_progress' => Arr::get($metadata, 'todo_progress', []),
                'updated_at' => $this->optionalIso($task->updated_at),
                'updated_human' => $this->optionalHuman($task->updated_at),
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
        return 'orchestration_tasks_list';
    }

    public static function summaryTitle(): string
    {
        return 'List orchestration tasks';
    }

    public static function summaryDescription(): string
    {
        return 'Filter tasks by sprint, status, delegated agent, or text search.';
    }

    public static function schemaSummary(): array
    {
        return [
            'sprint[]' => 'SPRINT-XX or numeric code',
            'delegation_status[]' => 'completed|in_progress|assigned|blocked|unassigned',
            'status[]' => 'work item status filter',
            'agent' => 'recommended agent slug',
            'limit' => 'defaults to 20',
        ];
    }
}
