<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Illuminate\Database\Eloquent\Model;

class OrchestrationHashService
{
    public function generateSprintHash(OrchestrationSprint $sprint): string
    {
        return hash('sha256', 
            $sprint->sprint_code . 
            json_encode($sprint->metadata ?? []) . 
            ($sprint->updated_at ? $sprint->updated_at->timestamp : time())
        );
    }

    public function generateTaskHash(OrchestrationTask $task): string
    {
        return hash('sha256', 
            $task->task_code . 
            json_encode($task->metadata ?? []) . 
            ($task->updated_at ? $task->updated_at->timestamp : time())
        );
    }

    public function verifyHash(Model $entity, string $hash): bool
    {
        $currentHash = match (get_class($entity)) {
            OrchestrationSprint::class => $this->generateSprintHash($entity),
            OrchestrationTask::class => $this->generateTaskHash($entity),
            default => null,
        };

        return $currentHash === $hash;
    }

    public function detectChanges(Model $entity, array $newData): array
    {
        $changes = [];
        
        foreach ($newData as $key => $value) {
            if (isset($entity->$key) && $entity->$key !== $value) {
                $changes[$key] = [
                    'old' => $entity->$key,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
