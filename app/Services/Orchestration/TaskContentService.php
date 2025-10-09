<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationArtifact;
use App\Models\TaskActivity;
use App\Models\WorkItem;
use App\Services\Orchestration\Artifacts\ContentStore;

class TaskContentService
{
    protected ContentStore $contentStore;

    protected int $maxInlineSizeBytes = 10 * 1024 * 1024; // 10MB

    public function __construct(ContentStore $contentStore)
    {
        $this->contentStore = $contentStore;
    }

    public function updateContent(
        WorkItem $task,
        string $field,
        string $content,
        ?string $agentId = null,
        ?int $userId = null
    ): void {
        $validFields = ['agent_content', 'plan_content', 'context_content', 'todo_content', 'summary_content'];
        
        if (! in_array($field, $validFields)) {
            throw new \InvalidArgumentException("Invalid content field: {$field}");
        }

        $oldContent = $task->{$field};
        $contentSize = strlen($content);

        if ($contentSize > $this->maxInlineSizeBytes) {
            $artifact = $this->storeAsArtifact($task, $field, $content);
            
            $task->{$field} = "fe://{$artifact->fe_uri}";
            
            TaskActivity::logArtifact(
                taskId: $task->id,
                artifactId: $artifact->id,
                feUri: $artifact->fe_uri,
                filename: "{$field}.txt",
                sizeBytes: $contentSize,
                agentId: $agentId,
                userId: $userId
            );
        } else {
            $task->{$field} = $content;
            
            TaskActivity::logContentUpdate(
                taskId: $task->id,
                field: $field,
                action: 'updated',
                agentId: $agentId,
                userId: $userId,
                description: "Updated {$field}",
                metadata: [
                    'size_bytes' => $contentSize,
                    'previous_size_bytes' => strlen($oldContent ?? ''),
                ]
            );
        }

        $task->save();
    }

    protected function storeAsArtifact(WorkItem $task, string $field, string $content): OrchestrationArtifact
    {
        $hash = $this->contentStore->put($content);
        $filename = "{$field}.txt";
        
        $artifact = OrchestrationArtifact::create([
            'task_id' => $task->id,
            'hash' => $hash,
            'filename' => $filename,
            'mime_type' => 'text/plain',
            'size_bytes' => strlen($content),
            'fe_uri' => $this->contentStore->formatUri($hash, $task->id, $filename),
            'storage_path' => $this->contentStore->getHashPath($hash),
            'metadata' => [
                'content_field' => $field,
                'overflow' => true,
            ],
        ]);

        return $artifact;
    }

    public function getContent(WorkItem $task, string $field): ?string
    {
        $validFields = ['agent_content', 'plan_content', 'context_content', 'todo_content', 'summary_content'];
        
        if (! in_array($field, $validFields)) {
            throw new \InvalidArgumentException("Invalid content field: {$field}");
        }

        $content = $task->{$field};

        if (! $content) {
            return null;
        }

        if (str_starts_with($content, 'fe://')) {
            $parsed = $this->contentStore->parseUri($content);
            if (! $parsed) {
                return $content;
            }

            return $this->contentStore->get($parsed['hash']);
        }

        return $content;
    }

    public function isArtifactReference(string $content): bool
    {
        return str_starts_with($content, 'fe://');
    }

    public function getContentSize(WorkItem $task, string $field): int
    {
        $content = $this->getContent($task, $field);
        
        return $content ? strlen($content) : 0;
    }

    public function migrateToArtifactIfNeeded(WorkItem $task, string $field): bool
    {
        $content = $task->{$field};
        
        if (! $content || $this->isArtifactReference($content)) {
            return false;
        }

        $contentSize = strlen($content);

        if ($contentSize > $this->maxInlineSizeBytes) {
            $this->updateContent($task, $field, $content);
            return true;
        }

        return false;
    }
}
