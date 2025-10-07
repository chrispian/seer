<?php

namespace App\Tools\Orchestration;

use App\Models\AgentProfile;
use App\Models\Message;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MessagesCheckTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_messages_check';
    protected string $title = 'Check agent inbox messages';
    protected string $description = 'Check inbox messages for an agent, optionally filtering by status (unread/all).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent_id' => $schema->string()->required()->description('Agent UUID or slug'),
            'status' => $schema->enum(['unread', 'all'])->default('unread'),
            'limit' => $schema->integer()->min(1)->max(50)->default(25),
        ];
    }

    public function handle(Request $request): Response
    {
        $agentIdentifier = (string) $request->get('agent_id');
        $status = $request->get('status', 'unread');
        $limit = (int) $request->get('limit', 25);

        $agent = $this->resolveAgent($agentIdentifier);

        $query = Message::query()->toAgent($agent->id);

        if ($status === 'unread') {
            $query->unread();
        }

        $messages = $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'stream' => $msg->stream,
                'type' => $msg->type,
                'task_id' => $msg->task_id,
                'from_agent_id' => $msg->from_agent_id,
                'headers' => $msg->headers,
                'envelope' => $msg->envelope,
                'read_at' => $msg->read_at?->toIso8601String(),
                'created_at' => $msg->created_at->toIso8601String(),
            ]);

        $unreadCount = Message::query()->toAgent($agent->id)->unread()->count();

        return Response::json([
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
            ],
            'messages' => $messages,
            'meta' => [
                'count' => $messages->count(),
                'unread_count' => $unreadCount,
                'showing' => $status,
            ],
        ]);
    }

    protected function resolveAgent(string $identifier): AgentProfile
    {
        if (str($identifier)->isUuid()) {
            return AgentProfile::findOrFail($identifier);
        }

        return AgentProfile::where('slug', $identifier)
            ->orWhere('name', $identifier)
            ->firstOrFail();
    }

    public static function summaryName(): string
    {
        return 'orchestration_messages_check';
    }

    public static function summaryTitle(): string
    {
        return 'Check agent inbox';
    }

    public static function summaryDescription(): string
    {
        return 'List inbox messages for an agent with optional unread filter.';
    }

    public static function schemaSummary(): array
    {
        return [
            'agent_id' => 'Agent UUID or slug',
            'status' => 'unread|all (default: unread)',
            'limit' => 'Max messages to return (default: 25)',
        ];
    }
}
