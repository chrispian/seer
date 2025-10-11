<?php

namespace App\Tools\Orchestration;

use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionTaskDeactivateTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_task_deactivate';

    protected string $title = 'Deactivate task from session';

    protected string $description = 'Remove active task from current session context.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'summary' => $schema->string()->optional()->description('Task summary or completion note'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        try {
            $sessionKey = $request->get('session_key');
            $summary = $request->get('summary');

            $session = WorkSession::where('session_key', $sessionKey)->firstOrFail();
            $activeTask = $sessionManager->getActiveContext($session->id, 'task');

            if (!$activeTask) {
                return Response::json([
                    'success' => false,
                    'error' => 'No active task in session',
                    'instructions' => [
                        'note' => 'Activate a task first with orchestration_session_task_activate',
                    ],
                ]);
            }

            $taskId = $activeTask['id'];
            $taskCode = $activeTask['data']['task_code'] ?? $taskId;

            if ($summary) {
                $sessionManager->logActivity($session->id, 'note', [
                    'description' => "Task summary: {$summary}",
                    'task_id' => $taskId,
                ]);
            }

            $popped = $sessionManager->popContext($session->id, 'task');

            return Response::json([
                'success' => true,
                'deactivated_task' => [
                    'code' => $taskCode,
                    'summary' => $summary,
                ],
                'instructions' => [
                    'next_actions' => [
                        'Activate another task to continue working',
                        'Or end session if done',
                    ],
                    'suggested_tools' => [
                        'orchestration_session_task_activate',
                        'orchestration_session_end',
                    ],
                    'note' => 'Task removed from active context. Use orchestration_task_status to mark as completed if done.',
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
        return 'orchestration_session_task_deactivate';
    }

    public static function summaryTitle(): string
    {
        return 'Deactivate task from session';
    }

    public static function summaryDescription(): string
    {
        return 'Remove task from active session context.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'summary' => 'Optional task summary/note',
        ];
    }
}
