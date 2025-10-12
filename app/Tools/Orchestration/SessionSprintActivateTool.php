<?php

namespace App\Tools\Orchestration;

use App\Models\Sprint;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionSprintActivateTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_sprint_activate';

    protected string $title = 'Activate sprint in session';

    protected string $description = 'Set active sprint in current session context.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'sprint_code' => $schema->string()->required()->description('Sprint code (SPRINT-XX)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        try {
            $sessionKey = $request->get('session_key');
            $sprintCode = $request->get('sprint_code');

            if (is_numeric($sprintCode)) {
                $sprintCode = 'SPRINT-' . $sprintCode;
            }

            $session = WorkSession::where('session_key', $sessionKey)->firstOrFail();
            $sprint = Sprint::where('code', $sprintCode)->firstOrFail();

            $sessionManager->pushContext($session->id, 'sprint', $sprint->id, [
                'code' => $sprint->code,
                'title' => $sprint->title,
            ]);

            $stats = $sprint->stats ?? ['total' => 0, 'completed' => 0];

            return Response::json([
                'success' => true,
                'sprint' => [
                    'code' => $sprint->code,
                    'title' => $sprint->title,
                    'priority' => $sprint->priority,
                    'status' => $sprint->status,
                    'stats' => $stats,
                ],
                'instructions' => [
                    'next_actions' => [
                        'Activate a task to start working',
                        'View tasks with orchestration_tasks_list',
                    ],
                    'suggested_tools' => [
                        'orchestration_session_task_activate',
                        'orchestration_tasks_list',
                    ],
                    'context_reminder' => [
                        'session' => $sessionKey,
                        'active_sprint' => $sprint->code,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function summaryName(): string
    {
        return 'orchestration_session_sprint_activate';
    }

    public static function summaryTitle(): string
    {
        return 'Activate sprint in session';
    }

    public static function summaryDescription(): string
    {
        return 'Set active sprint context for current session.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'sprint_code' => 'Sprint code (SPRINT-XX or numeric)',
        ];
    }
}
