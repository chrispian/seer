<?php

namespace App\Tools\Orchestration;

use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionTaskNoteTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_task_note';

    protected string $title = 'Add note to active task';

    protected string $description = 'Add a quick note or observation to the active task in current session.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'note' => $schema->string()->required()->description('Note or observation'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        try {
            $sessionKey = $request->get('session_key');
            $note = $request->get('note');

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

            $sessionManager->logActivity($session->id, 'note', [
                'description' => $note,
                'task_id' => $taskId,
            ]);

            return Response::json([
                'success' => true,
                'task' => [
                    'code' => $taskCode,
                    'note_added' => true,
                ],
                'instructions' => [
                    'next_actions' => [
                        'Continue working on task',
                        'Add more notes or context as needed',
                    ],
                    'note' => 'Note logged to session activities. Use orchestration_session_task_update to add formal context.',
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
        return 'orchestration_session_task_note';
    }

    public static function summaryTitle(): string
    {
        return 'Add note to active task';
    }

    public static function summaryDescription(): string
    {
        return 'Quick note/observation for active task.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'note' => 'Note or observation text',
        ];
    }
}
