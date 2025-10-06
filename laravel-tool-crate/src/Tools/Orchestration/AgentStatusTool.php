<?php

namespace HollisLabs\ToolCrate\Tools\Orchestration;

use HollisLabs\ToolCrate\Support\Orchestration\ModelResolver;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AgentStatusTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration.agents.status';
    protected string $title = 'Update agent status';
    protected string $description = 'Set an agent profile to active, inactive, or archived.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->required()->description('Agent slug or UUID'),
            'status' => $schema->string()->enum(['active', 'inactive', 'archived'])->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'agent' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $agent = $service->setStatus($validated['agent'], $validated['status']);

        $detail = $service->detail($agent, [
            'assignments_limit' => 5,
            'include_history' => false,
        ]);

        return Response::json($detail);
    }

    public static function summaryName(): string
    {
        return 'orchestration.agents.status';
    }

    public static function summaryTitle(): string
    {
        return 'Update agent status';
    }

    public static function summaryDescription(): string
    {
        return 'Set agent profile to active, inactive, or archived.';
    }

    public static function schemaSummary(): array
    {
        return [
            'agent' => 'Agent slug/UUID',
            'status' => 'active|inactive|archived',
        ];
    }
}
