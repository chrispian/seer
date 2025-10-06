<?php

namespace App\Tools\Orchestration;

use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintSaveTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_sprints_save';
    protected string $title = 'Create or update sprint';
    protected string $description = 'Create a new sprint or update an existing one, including dates and metadata.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->required()->description('Sprint code (`SPRINT-XX`) or number'),
            'title' => $schema->string()->description('Human-friendly sprint title'),
            'priority' => $schema->string()->description('Sprint priority label'),
            'estimate' => $schema->string()->description('Estimate string'),
            'status' => $schema->string()->description('Status text (e.g. Planned/In Progress/Completed)'),
            'notes' => $schema->array()->items($schema->string())->description('Notes array (replaces existing notes when provided)'),
            'starts_on' => $schema->string()->description('Start date (Y-m-d)'),
            'ends_on' => $schema->string()->description('End date (Y-m-d)'),
            'meta' => $schema->object()->description('Additional metadata entries'),
            'upsert' => $schema->boolean()->default(true)->description('When true, update existing sprint instead of failing'),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'title' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'estimate' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'array'],
            'starts_on' => ['nullable', 'string'],
            'ends_on' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'upsert' => ['nullable', 'boolean'],
        ]);

        $service = ModelResolver::resolveService('sprint_service', 'App\\Services\\SprintOrchestrationService');

        $upsert = Arr::pull($validated, 'upsert', true);

        $sprint = $service->create($validated, (bool) $upsert);

        $detail = $service->detail($sprint, [
            'include_tasks' => true,
            'tasks_limit' => 10,
        ]);

        return Response::json($detail);
    }

    public static function summaryName(): string
    {
        return 'orchestration_sprints_save';
    }

    public static function summaryTitle(): string
    {
        return 'Create or update sprint';
    }

    public static function summaryDescription(): string
    {
        return 'Upsert sprint metadata, dates, and notes.';
    }

    public static function schemaSummary(): array
    {
        return [
            'code' => 'Sprint code or number',
            'title' => 'Optional title override',
            'status' => 'Meta status label',
        ];
    }
}
