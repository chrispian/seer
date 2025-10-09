<?php

namespace App\Tools\Orchestration;

use App\Models\AgentProfile;
use App\Models\Message;
use App\Models\WorkItem;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class HandoffTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_handoff';

    protected string $title = 'Handoff work to another agent';

    protected string $description = 'Create a handoff request message to transfer work to another agent.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_agent_id' => $schema->string()->required()->description('Source agent UUID or slug'),
            'to_agent_id' => $schema->string()->required()->description('Target agent UUID or slug'),
            'task_id' => $schema->string()->description('Task UUID or task code'),
            'context_pack' => $schema->object()->description('Optional context pack to include'),
            'reason' => $schema->string()->description('Reason for handoff'),
        ];
    }

    public function handle(Request $request): Response
    {
        $fromAgent = $this->resolveAgent((string) $request->get('from_agent_id'));
        $toAgent = $this->resolveAgent((string) $request->get('to_agent_id'));

        $task = null;
        if ($request->get('task_id')) {
            $task = $this->resolveTask((string) $request->get('task_id'));
        }

        $contextPack = $request->get('context_pack', []);
        $reason = $request->get('reason', 'Agent handoff request');

        $message = Message::create([
            'stream' => "agents.{$toAgent->slug}.inbox",
            'type' => 'handoff.request',
            'task_id' => $task?->id,
            'to_agent_id' => $toAgent->id,
            'from_agent_id' => $fromAgent->id,
            'headers' => [
                'reason' => $reason,
                'handoff_at' => now()->toIso8601String(),
            ],
            'envelope' => [
                'type' => 'handoff',
                'from_agent' => [
                    'id' => $fromAgent->id,
                    'name' => $fromAgent->name,
                    'slug' => $fromAgent->slug,
                ],
                'to_agent' => [
                    'id' => $toAgent->id,
                    'name' => $toAgent->name,
                    'slug' => $toAgent->slug,
                ],
                'task' => $task ? [
                    'id' => $task->id,
                    'task_code' => $task->metadata['task_code'] ?? null,
                    'task_name' => $task->metadata['task_name'] ?? null,
                ] : null,
                'reason' => $reason,
                'context_pack' => $contextPack,
            ],
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Handoff request created',
            'handoff' => [
                'message_id' => $message->id,
                'from_agent' => $fromAgent->slug,
                'to_agent' => $toAgent->slug,
                'task_code' => $task ? ($task->metadata['task_code'] ?? null) : null,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    protected function resolveAgent(string $identifier): AgentProfile
    {
        if (Str::isUuid($identifier)) {
            return AgentProfile::findOrFail($identifier);
        }

        return AgentProfile::where('slug', $identifier)
            ->orWhere('name', $identifier)
            ->firstOrFail();
    }

    protected function resolveTask(string $identifier): WorkItem
    {
        if (Str::isUuid($identifier)) {
            return WorkItem::findOrFail($identifier);
        }

        return WorkItem::where('metadata->task_code', $identifier)
            ->orWhere('metadata->task_code', strtoupper($identifier))
            ->firstOrFail();
    }

    public static function summaryName(): string
    {
        return 'orchestration_handoff';
    }

    public static function summaryTitle(): string
    {
        return 'Handoff to another agent';
    }

    public static function summaryDescription(): string
    {
        return 'Transfer work to another agent with optional context pack.';
    }

    public static function schemaSummary(): array
    {
        return [
            'from_agent_id' => 'Source agent UUID or slug',
            'to_agent_id' => 'Target agent UUID or slug',
            'task_id' => 'Optional task UUID or code',
            'context_pack' => 'Optional context data',
            'reason' => 'Handoff reason',
        ];
    }
}
