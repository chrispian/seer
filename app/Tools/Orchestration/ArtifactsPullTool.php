<?php

namespace App\Tools\Orchestration;

use App\Models\OrchestrationArtifact;
use App\Models\WorkItem;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ArtifactsPullTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_artifacts_pull';

    protected string $title = 'Pull task artifacts';

    protected string $description = 'List artifacts for a task, optionally including content.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->string()->required()->description('Task UUID or task code'),
            'download_content' => $schema->boolean()->default(false)->description('Include artifact content in response'),
        ];
    }

    public function handle(Request $request): Response
    {
        $taskIdentifier = (string) $request->get('task_id');
        $downloadContent = (bool) $request->get('download_content', false);

        $task = $this->resolveTask($taskIdentifier);

        $artifacts = OrchestrationArtifact::byTask($task->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($artifact) use ($downloadContent) {
                $data = [
                    'id' => $artifact->id,
                    'filename' => $artifact->filename,
                    'hash' => $artifact->hash,
                    'mime_type' => $artifact->mime_type,
                    'size_bytes' => $artifact->size_bytes,
                    'size_formatted' => $artifact->size_formatted,
                    'fe_uri' => $artifact->fe_uri,
                    'metadata' => $artifact->metadata,
                    'created_at' => $artifact->created_at->toIso8601String(),
                ];

                if ($downloadContent) {
                    $data['content'] = $artifact->content;
                }

                return $data;
            });

        return Response::json([
            'task' => [
                'id' => $task->id,
                'task_code' => $task->metadata['task_code'] ?? null,
            ],
            'artifacts' => $artifacts,
            'meta' => [
                'count' => $artifacts->count(),
                'total_size_bytes' => $artifacts->sum('size_bytes'),
                'content_included' => $downloadContent,
            ],
        ]);
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
        return 'orchestration_artifacts_pull';
    }

    public static function summaryTitle(): string
    {
        return 'Pull task artifacts';
    }

    public static function summaryDescription(): string
    {
        return 'List artifacts for a task with optional content download.';
    }

    public static function schemaSummary(): array
    {
        return [
            'task_id' => 'Task UUID or task code',
            'download_content' => 'Include content in response (default: false)',
        ];
    }
}
