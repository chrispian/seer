<?php

namespace App\Tools\Orchestration;

use App\Models\WorkSession;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionStatusTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_status';

    protected string $title = 'View session status';

    protected string $description = 'View current session status, context stack, and recent activities.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'include_activities' => $schema->boolean()->default(true)->description('Include recent activities'),
            'activities_limit' => $schema->integer()->min(1)->max(20)->default(5),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $sessionKey = $request->get('session_key');
            $session = WorkSession::where('session_key', $sessionKey)
                ->with(['activeSprint', 'activeTask'])
                ->firstOrFail();

            $duration = gmdate('H:i:s', $session->getDurationInSecondsAttribute());
            $contextStack = $session->context_stack ?? [];

            $response = [
                'success' => true,
                'session' => [
                    'session_key' => $session->session_key,
                    'type' => $session->session_type,
                    'status' => $session->status,
                    'source' => $session->source,
                    'duration' => $duration,
                    'duration_seconds' => $session->getDurationInSecondsAttribute(),
                    'started_at' => $session->started_at->toIso8601String(),
                ],
                'context_stack' => $contextStack,
                'active_context' => [
                    'sprint' => $session->activeSprint ? [
                        'code' => $session->activeSprint->code,
                        'title' => $session->activeSprint->title,
                    ] : null,
                    'task' => $session->activeTask ? [
                        'code' => $session->activeTask->metadata['task_code'] ?? $session->activeTask->id,
                        'name' => $session->activeTask->metadata['task_name'] ?? null,
                        'status' => $session->activeTask->delegation_status,
                    ] : null,
                ],
            ];

            if ($request->get('include_activities', true)) {
                $limit = (int) $request->get('activities_limit', 5);
                $activities = $session->activities()->recent()->limit($limit)->get();

                $response['recent_activities'] = $activities->map(function ($activity) {
                    return [
                        'type' => $activity->activity_type,
                        'description' => $activity->description,
                        'occurred_at' => $activity->occurred_at->toIso8601String(),
                        'command' => $activity->command,
                    ];
                })->toArray();
            }

            return Response::json($response);
        } catch (\Exception $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function summaryName(): string
    {
        return 'orchestration_session_status';
    }

    public static function summaryTitle(): string
    {
        return 'View session status';
    }

    public static function summaryDescription(): string
    {
        return 'Current session info, context, and activities.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'include_activities' => 'Include recent activities (default: true)',
            'activities_limit' => 'Activities limit (default: 5)',
        ];
    }
}
