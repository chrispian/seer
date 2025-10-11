<?php

namespace App\Tools\Orchestration;

use App\Services\Orchestration\SessionManager;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionStartTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_start';

    protected string $title = 'Start work session';

    protected string $description = 'Create a new work session for context tracking and activity logging.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent_id' => $schema->string()->optional()->description('Agent UUID or slug'),
            'session_type' => $schema->string()->enum(['work', 'planning', 'review'])->default('work'),
            'source' => $schema->string()->enum(['cli', 'mcp', 'api', 'gui'])->default('mcp'),
            'metadata' => $schema->object()->optional()->description('Additional metadata'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        $session = $sessionManager->startSession([
            'agent_id' => $request->get('agent_id'),
            'user_id' => auth()->id(),
            'session_type' => $request->get('session_type', 'work'),
            'source' => $request->get('source', 'mcp'),
            'metadata' => $request->get('metadata', []),
        ]);

        return Response::json([
            'success' => true,
            'session' => [
                'session_key' => $session->session_key,
                'id' => $session->id,
                'type' => $session->session_type,
                'status' => $session->status,
                'started_at' => $session->started_at->toIso8601String(),
            ],
            'instructions' => [
                'next_actions' => [
                    'Activate sprint to set working context',
                    'Start or resume a task',
                ],
                'suggested_tools' => [
                    'orchestration_session_sprint_activate',
                    'orchestration_session_task_activate',
                ],
                'note' => 'Store session_key in memory for all subsequent orchestration calls',
            ],
        ]);
    }

    public static function summaryName(): string
    {
        return 'orchestration_session_start';
    }

    public static function summaryTitle(): string
    {
        return 'Start work session';
    }

    public static function summaryDescription(): string
    {
        return 'Create new work session with context tracking.';
    }

    public static function schemaSummary(): array
    {
        return [
            'agent_id' => 'Optional agent UUID',
            'session_type' => 'work|planning|review (default: work)',
            'source' => 'cli|mcp|api|gui (default: mcp)',
            'metadata' => 'Optional metadata object',
        ];
    }
}
