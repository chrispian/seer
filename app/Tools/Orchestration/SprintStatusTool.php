<?php

namespace App\Tools\Orchestration;

use App\Commands\Orchestration\Sprint\UpdateStatusCommand;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SprintStatusTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_sprints_status';

    protected string $title = 'Update sprint status';

    protected string $description = 'Change the status meta for a sprint and optionally append a note.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint' => $schema->string()->required()->description('Sprint code (e.g. SPRINT-62) or UUID'),
            'status' => $schema->string()->required()->description('Status label (e.g. Planned, In Progress, Completed)'),
            'note' => $schema->string()->description('Optional note appended to sprint notes list'),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'sprint' => ['required', 'string'],
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $command = new UpdateStatusCommand([
            'code' => $validated['sprint'],
            'status' => $validated['status'],
            'note' => $validated['note'] ?? null,
        ]);
        
        $command->setContext('mcp');
        $result = $command->handle();

        return Response::json($result);
    }

    public static function summaryName(): string
    {
        return 'orchestration_sprints_status';
    }

    public static function summaryTitle(): string
    {
        return 'Update sprint status';
    }

    public static function summaryDescription(): string
    {
        return 'Set sprint status meta and append a note if provided.';
    }

    public static function schemaSummary(): array
    {
        return [
            'sprint' => 'Sprint code or UUID',
            'status' => 'Status label',
            'note' => 'Optional note',
        ];
    }
}
