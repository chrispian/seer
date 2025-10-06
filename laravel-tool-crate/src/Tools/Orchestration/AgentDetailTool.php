<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AgentDetailTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration.agents.detail';
    protected string $title = 'Show orchestration agent detail';
    protected string $description = 'Return agent profile, stats, and recent assignments.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->required()->description('Agent slug, UUID, or name'),
            'assignments_limit' => $schema->integer()->minimum(1)->maximum(25)->default(10),
            'include_history' => $schema->boolean()->default(true),
        ];
    }

    public function handle(Request $request): Response
    {
        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $payload = $service->detail($request->get('agent'), [
            'assignments_limit' => (int) $request->get('assignments_limit', 10),
            'include_history' => (bool) $request->get('include_history', true),
        ]);

        return Response::json($payload);
    }

    public static function summaryName(): string
    {
        return 'orchestration.agents.detail';
    }

    public static function summaryTitle(): string
    {
        return 'Show orchestration agent detail';
    }

    public static function summaryDescription(): string
    {
        return 'Agent profile, stats, and latest assignments.';
    }

    public static function schemaSummary(): array
    {
        return [
            'agent' => 'Agent slug/UUID',
            'assignments_limit' => 'defaults to 10',
        ];
    }
}
