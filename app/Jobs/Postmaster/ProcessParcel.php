<?php

namespace App\Jobs\Postmaster;

use App\Models\Message;
use App\Models\OrchestrationArtifact;
use App\Models\WorkItem;
use App\Services\Orchestration\Artifacts\ContentStore;
use App\Services\Orchestration\Security\SecretRedactor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessParcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    protected array $parcel;

    protected string $taskId;

    public function __construct(array $parcel, string $taskId)
    {
        $this->parcel = $parcel;
        $this->taskId = $taskId;
        $this->onQueue('postmaster');
    }

    public function handle(ContentStore $contentStore, SecretRedactor $redactor): array
    {
        Log::info('Postmaster: Processing parcel', [
            'task_id' => $this->taskId,
            'parcel_type' => $this->parcel['type'] ?? 'unknown',
        ]);

        $task = WorkItem::findOrFail($this->taskId);

        $rewrittenEnvelope = $this->parcel;
        $artifacts = [];
        $manifest = [
            'task_id' => $this->taskId,
            'processed_at' => now()->toIso8601String(),
            'artifacts' => [],
        ];

        if (isset($this->parcel['attachments']) && is_array($this->parcel['attachments'])) {
            foreach ($this->parcel['attachments'] as $key => $attachment) {
                $artifact = $this->processAttachment($contentStore, $task, $key, $attachment);

                if ($artifact) {
                    $artifacts[] = $artifact;
                    $manifest['artifacts'][] = [
                        'key' => $key,
                        'filename' => $artifact->filename,
                        'hash' => $artifact->hash,
                        'fe_uri' => $artifact->fe_uri,
                        'size_bytes' => $artifact->size_bytes,
                    ];

                    $rewrittenEnvelope['attachments'][$key] = [
                        'fe_uri' => $artifact->fe_uri,
                        'hash' => $artifact->hash,
                        'filename' => $artifact->filename,
                        'size_bytes' => $artifact->size_bytes,
                        'mime_type' => $artifact->mime_type,
                    ];
                }
            }
        }

        if (isset($this->parcel['inline_content'])) {
            foreach ($this->parcel['inline_content'] as $key => $content) {
                if (is_string($content) && strlen($content) > (5 * 1024 * 1024)) {
                    $artifact = $this->processInlineContent($contentStore, $task, $key, $content);

                    if ($artifact) {
                        $artifacts[] = $artifact;
                        $manifest['artifacts'][] = [
                            'key' => $key,
                            'filename' => $artifact->filename,
                            'hash' => $artifact->hash,
                            'fe_uri' => $artifact->fe_uri,
                            'size_bytes' => $artifact->size_bytes,
                        ];

                        $rewrittenEnvelope['inline_content'][$key] = [
                            'fe_uri' => $artifact->fe_uri,
                            'hash' => $artifact->hash,
                        ];
                    }
                }
            }
        }

        $manifestContent = json_encode($manifest, JSON_PRETTY_PRINT);
        $manifestHash = $contentStore->put($manifestContent);

        $manifestArtifact = OrchestrationArtifact::create([
            'task_id' => $task->id,
            'hash' => $manifestHash,
            'filename' => 'manifest.json',
            'mime_type' => 'application/json',
            'size_bytes' => strlen($manifestContent),
            'metadata' => ['type' => 'manifest'],
            'fe_uri' => $contentStore->formatUri($manifestHash, $this->taskId, 'manifest.json'),
            'storage_path' => $contentStore->getHashPath($manifestHash),
        ]);

        $message = Message::create([
            'stream' => $this->parcel['stream'] ?? "tasks.{$this->taskId}.mailbox",
            'type' => 'postmaster.delivery',
            'task_id' => $task->id,
            'to_agent_id' => $this->parcel['to_agent_id'] ?? null,
            'from_agent_id' => null,
            'headers' => [
                'original_type' => $this->parcel['type'] ?? null,
                'artifacts_count' => count($artifacts),
                'manifest_uri' => $manifestArtifact->fe_uri,
            ],
            'envelope' => $rewrittenEnvelope,
        ]);

        Log::info('Postmaster: Parcel processed', [
            'task_id' => $this->taskId,
            'message_id' => $message->id,
            'artifacts_count' => count($artifacts),
            'manifest_hash' => $manifestHash,
        ]);

        return [
            'message_id' => $message->id,
            'artifacts_count' => count($artifacts),
            'manifest_uri' => $manifestArtifact->fe_uri,
            'rewritten_envelope' => $rewrittenEnvelope,
        ];
    }

    protected function processAttachment(ContentStore $contentStore, WorkItem $task, string $key, array $attachment): ?OrchestrationArtifact
    {
        if (! isset($attachment['content'])) {
            return null;
        }

        $content = $attachment['content'];
        $redactor = app(SecretRedactor::class);
        $content = $redactor->redact($content);

        $filename = $attachment['filename'] ?? "attachment-{$key}";
        $mimeType = $attachment['mime_type'] ?? 'application/octet-stream';

        $hash = $contentStore->put($content, [
            'source' => 'parcel_attachment',
            'key' => $key,
        ]);

        return OrchestrationArtifact::create([
            'task_id' => $task->id,
            'hash' => $hash,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($content),
            'metadata' => [
                'source' => 'parcel_attachment',
                'key' => $key,
            ],
            'fe_uri' => $contentStore->formatUri($hash, $task->id, $filename),
            'storage_path' => $contentStore->getHashPath($hash),
        ]);
    }

    protected function processInlineContent(ContentStore $contentStore, WorkItem $task, string $key, string $content): ?OrchestrationArtifact
    {
        $redactor = app(SecretRedactor::class);
        $content = $redactor->redact($content);

        $hash = $contentStore->put($content, [
            'source' => 'inline_content',
            'key' => $key,
        ]);

        $filename = "{$key}.txt";

        return OrchestrationArtifact::create([
            'task_id' => $task->id,
            'hash' => $hash,
            'filename' => $filename,
            'mime_type' => 'text/plain',
            'size_bytes' => strlen($content),
            'metadata' => [
                'source' => 'inline_content',
                'key' => $key,
            ],
            'fe_uri' => $contentStore->formatUri($hash, $task->id, $filename),
            'storage_path' => $contentStore->getHashPath($hash),
        ]);
    }
}
