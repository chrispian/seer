<?php

namespace App\Http\Controllers\Orchestration;

use App\Http\Controllers\Controller;
use App\Models\OrchestrationArtifact;
use App\Models\WorkItem;
use App\Services\Orchestration\Artifacts\ContentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtifactsController extends Controller
{
    public function __construct(
        protected ContentStore $contentStore
    ) {}

    public function createArtifact(Request $request, string $taskId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'filename' => 'required|string|max:255',
            'mime_type' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        $task = WorkItem::findOrFail($taskId);
        
        $content = $validated['content'];
        $hash = $this->contentStore->put($content, $validated['metadata'] ?? []);
        
        $artifact = OrchestrationArtifact::create([
            'task_id' => $task->id,
            'hash' => $hash,
            'filename' => $validated['filename'],
            'mime_type' => $validated['mime_type'] ?? 'application/octet-stream',
            'size_bytes' => strlen($content),
            'metadata' => $validated['metadata'] ?? [],
            'fe_uri' => $this->contentStore->formatUri($hash, $taskId, $validated['filename']),
            'storage_path' => $this->contentStore->getHashPath($hash),
        ]);

        return response()->json([
            'success' => true,
            'artifact' => [
                'id' => $artifact->id,
                'hash' => $artifact->hash,
                'filename' => $artifact->filename,
                'mime_type' => $artifact->mime_type,
                'size_bytes' => $artifact->size_bytes,
                'size_formatted' => $artifact->size_formatted,
                'fe_uri' => $artifact->fe_uri,
                'created_at' => $artifact->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function listTaskArtifacts(string $taskId): JsonResponse
    {
        $task = WorkItem::findOrFail($taskId);
        
        $artifacts = OrchestrationArtifact::byTask($task->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $artifacts->map(fn($artifact) => [
                'id' => $artifact->id,
                'hash' => $artifact->hash,
                'filename' => $artifact->filename,
                'mime_type' => $artifact->mime_type,
                'size_bytes' => $artifact->size_bytes,
                'size_formatted' => $artifact->size_formatted,
                'fe_uri' => $artifact->fe_uri,
                'metadata' => $artifact->metadata,
                'created_at' => $artifact->created_at->toIso8601String(),
            ]),
            'meta' => [
                'task_id' => $task->id,
                'count' => $artifacts->count(),
                'total_size_bytes' => $artifacts->sum('size_bytes'),
            ],
        ]);
    }

    public function downloadArtifact(string $artifactId): StreamedResponse
    {
        $artifact = OrchestrationArtifact::findOrFail($artifactId);
        
        $content = $artifact->content;
        
        if ($content === null) {
            abort(404, 'Artifact content not found in storage');
        }

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            $artifact->filename,
            [
                'Content-Type' => $artifact->mime_type ?? 'application/octet-stream',
                'Content-Length' => $artifact->size_bytes,
                'Cache-Control' => 'public, max-age=31536000',
                'X-Artifact-Hash' => $artifact->hash,
                'X-FE-URI' => $artifact->fe_uri,
            ]
        );
    }
}
