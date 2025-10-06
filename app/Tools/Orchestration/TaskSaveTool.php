<?php

namespace App\Tools\Orchestration;

use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TaskSaveTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_tasks_save';
    protected string $title = 'Create or update task';
    protected string $description = 'Create a new work item or update an existing one, including metadata and delegation settings.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_code' => $schema->string()->required()->description('Task code (e.g., T-ART-02-CAS)'),
            'task_name' => $schema->string()->description('Human-friendly task name'),
            'type' => $schema->string()->description('Task type (task, feature, bug, etc.)'),
            'status' => $schema->string()->description('Work item status (todo, in_progress, done, etc.)'),
            'priority' => $schema->string()->description('Priority (low, medium, high, critical)'),
            'delegation_status' => $schema->string()->description('Delegation status (unassigned, assigned, in_progress, blocked, completed)'),
            'description' => $schema->string()->description('Task description or agent_content'),
            'agent_content' => $schema->string()->description('Detailed agent instructions'),
            'sprint_code' => $schema->string()->description('Associated sprint code (SPRINT-XX)'),
            'estimate_text' => $schema->string()->description('Time estimate (e.g., "3-4 hours")'),
            'estimated_hours' => $schema->number()->description('Numeric estimate in hours'),
            'dependencies' => $schema->array()->items($schema->string())->description('Task dependencies (array of task codes)'),
            'acceptance' => $schema->string()->description('Acceptance criteria'),
            'tags' => $schema->array()->items($schema->string())->description('Tags array'),
            'upsert' => $schema->boolean()->default(true)->description('When true, update existing task instead of failing'),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task_code' => ['required', 'string'],
            'task_name' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'delegation_status' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'agent_content' => ['nullable', 'string'],
            'sprint_code' => ['nullable', 'string'],
            'estimate_text' => ['nullable', 'string'],
            'estimated_hours' => ['nullable', 'numeric'],
            'dependencies' => ['nullable', 'array'],
            'dependencies.*' => ['string'],
            'acceptance' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'upsert' => ['nullable', 'boolean'],
        ]);

        $service = ModelResolver::resolveService('task_service', 'App\\Services\\TaskOrchestrationService');

        $upsert = Arr::pull($validated, 'upsert', true);

        $task = $service->create($validated, (bool) $upsert);

        $detail = $service->detail($task, [
            'assignments_limit' => 10,
            'include_history' => true,
        ]);

        return Response::json($detail);
    }

    public static function summaryName(): string
    {
        return 'orchestration_tasks_save';
    }

    public static function summaryTitle(): string
    {
        return 'Create or update task';
    }

    public static function summaryDescription(): string
    {
        return 'Upsert task metadata, delegation settings, and acceptance criteria.';
    }

    public static function schemaSummary(): array
    {
        return [
            'task_code' => 'Required task identifier',
            'task_name' => 'Optional title',
            'status' => 'Work item status',
            'delegation_status' => 'unassigned|assigned|in_progress|blocked|completed',
        ];
    }
}
