<?php

namespace App\Tools\Orchestration;

use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionEndTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_end';

    protected string $title = 'End work session';

    protected string $description = 'End the current work session with validation and summary.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'summary' => $schema->string()->description('Session summary'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        try {
            $sessionKey = $request->get('session_key');
            $session = WorkSession::where('session_key', $sessionKey)->firstOrFail();

            $validation = $sessionManager->validateCompletion($session->id);

            if (!$validation['valid']) {
                return Response::json([
                    'success' => false,
                    'error' => 'Session validation failed',
                    'validation' => $validation,
                    'instructions' => [
                        'required_actions' => array_map(fn($err) => $err, $validation['errors']),
                        'note' => 'Fix validation errors before ending session',
                    ],
                ]);
            }

            $session = $sessionManager->endSession($session->id, [
                'summary' => $request->get('summary'),
            ]);

            $duration = gmdate('H:i:s', $session->total_active_seconds);

            return Response::json([
                'success' => true,
                'session' => [
                    'session_key' => $session->session_key,
                    'duration' => $duration,
                    'duration_seconds' => $session->total_active_seconds,
                    'tasks_completed' => $session->tasks_completed,
                    'artifacts_created' => $session->artifacts_created,
                    'status' => $session->status,
                ],
                'validation' => $validation,
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
        return 'orchestration_session_end';
    }

    public static function summaryTitle(): string
    {
        return 'End work session';
    }

    public static function summaryDescription(): string
    {
        return 'Close session with validation and summary.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'summary' => 'Optional session summary',
        ];
    }
}
