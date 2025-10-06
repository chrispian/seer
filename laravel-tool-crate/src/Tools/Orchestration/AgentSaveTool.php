<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AgentSaveTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration.agents.save';
    protected string $title = 'Create or update agent profile';
    protected string $description = 'Upsert an agent profile including capabilities, tools, and status.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Agent UUID for updates'),
            'name' => $schema->string()->description('Agent name (required when creating)'),
            'slug' => $schema->string()->description('Agent slug (optional – generated when omitted)'),
            'type' => $schema->string()->description('Agent type slug (backend-engineer, etc.)'),
            'mode' => $schema->string()->description('Agent mode (implementation, planning, etc.)'),
            'status' => $schema->string()->description('active|inactive|archived'),
            'description' => $schema->string()->description('Agent description'),
            'capabilities' => $schema->array()->items($schema->string())->description('Capabilities list'),
            'constraints' => $schema->array()->items($schema->string())->description('Constraint list'),
            'tools' => $schema->array()->items($schema->string())->description('Tool list'),
            'metadata' => $schema->object()->description('Optional metadata map'),
            'upsert' => $schema->boolean()->default(true)->description('When true, create or update automatically'),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'slug' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'mode' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string'],
            'constraints' => ['nullable', 'array'],
            'constraints.*' => ['string'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'metadata' => ['nullable', 'array'],
            'upsert' => ['nullable', 'boolean'],
        ]);

        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $upsert = Arr::pull($validated, 'upsert', true);

        $agent = $service->save($validated, (bool) $upsert);

        $detail = $service->detail($agent, [
            'assignments_limit' => 10,
        ]);

        return Response::json($detail);
    }

    public static function summaryName(): string
    {
        return 'orchestration.agents.save';
    }

    public static function summaryTitle(): string
    {
        return 'Create or update agent profile';
    }

    public static function summaryDescription(): string
    {
        return 'Upsert agent profile data, capabilities, and status.';
    }

    public static function schemaSummary(): array
    {
        return [
            'name' => 'Required on create',
            'type' => 'agent type slug',
            'status' => 'active|inactive|archived',
        ];
    }
}
