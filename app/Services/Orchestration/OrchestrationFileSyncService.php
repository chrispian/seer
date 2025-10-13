<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class OrchestrationFileSyncService
{
    private string $sprintsPath;

    public function __construct()
    {
        $this->sprintsPath = base_path('delegation/sprints');
    }

    public function syncSprintToFile(OrchestrationSprint $sprint): bool
    {
        try {
            $sprintDir = $this->getSprintDirectory($sprint->sprint_code);
            $this->ensureDirectoryStructure($sprintDir);

            $sprintContent = $this->generateSprintMarkdown($sprint);
            $this->writeMarkdownFile("{$sprintDir}/SPRINT.md", $sprintContent);

            $readmeContent = $this->generateReadmeMarkdown($sprint);
            $this->writeMarkdownFile("{$sprintDir}/README.md", $readmeContent);

            Log::info('Sprint synced to file system', [
                'sprint_code' => $sprint->sprint_code,
                'path' => $sprintDir,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync sprint to file', [
                'sprint_id' => $sprint->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function syncTaskToFile(OrchestrationTask $task): bool
    {
        try {
            if (!$task->sprint_id) {
                Log::warning('Task has no sprint, skipping file sync', [
                    'task_id' => $task->id,
                ]);
                return false;
            }

            $sprint = $task->sprint;
            if (!$sprint) {
                return false;
            }

            $taskDir = $this->getTaskDirectory($sprint->sprint_code, $task->task_code);
            $this->ensureDirectoryStructure($taskDir);

            $taskContent = $this->generateTaskMarkdown($task);
            $this->writeMarkdownFile("{$taskDir}/TASK.md", $taskContent);

            if ($task->agent_config) {
                $this->writeYamlFile("{$taskDir}/AGENT.yml", $task->agent_config);
            }

            Log::info('Task synced to file system', [
                'task_code' => $task->task_code,
                'path' => $taskDir,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync task to file', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function ensureDirectoryStructure(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    public function writeMarkdownFile(string $path, string $content): void
    {
        File::put($path, $content);
    }

    public function writeYamlFile(string $path, array $data): void
    {
        $yaml = Yaml::dump($data, 4, 2);
        File::put($path, $yaml);
    }

    private function getSprintDirectory(string $sprintCode): string
    {
        return "{$this->sprintsPath}/{$sprintCode}";
    }

    private function getTaskDirectory(string $sprintCode, string $taskCode): string
    {
        return "{$this->sprintsPath}/{$sprintCode}/{$taskCode}";
    }

    private function generateSprintMarkdown(OrchestrationSprint $sprint): string
    {
        $metadata = $sprint->metadata ?? [];
        
        $content = "# Sprint: {$sprint->title}\n\n";
        $content .= "**Sprint ID**: `{$sprint->sprint_code}`  \n";
        $content .= "**Status**: {$sprint->status}  \n";
        
        if (isset($metadata['start_date'])) {
            $content .= "**Start Date**: {$metadata['start_date']}  \n";
        }
        
        if (isset($metadata['duration'])) {
            $content .= "**Duration**: {$metadata['duration']}  \n";
        }
        
        if ($sprint->owner) {
            $content .= "**Owner**: {$sprint->owner}  \n";
        }
        
        $content .= "\n---\n\n";
        
        if (isset($metadata['goal'])) {
            $content .= "## Sprint Goal\n\n{$metadata['goal']}\n\n---\n\n";
        }
        
        if (isset($metadata['context'])) {
            $content .= "## Context\n\n{$metadata['context']}\n\n---\n\n";
        }
        
        if (isset($metadata['success_metrics']) && is_array($metadata['success_metrics'])) {
            $content .= "## Success Metrics\n\n";
            foreach ($metadata['success_metrics'] as $metric) {
                $content .= "- ✅ {$metric}\n";
            }
            $content .= "\n---\n\n";
        }
        
        $content .= "## Tasks\n\n";
        foreach ($sprint->tasks as $task) {
            $content .= "- **{$task->task_code}**: {$task->title} ({$task->status})\n";
        }
        
        $content .= "\n---\n\n";
        $content .= "**Sprint Hash**: `{$sprint->hash}`  \n";
        $content .= "**Last Updated**: {$sprint->updated_at->toIso8601String()}  \n";
        
        return $content;
    }

    private function generateReadmeMarkdown(OrchestrationSprint $sprint): string
    {
        $content = "# {$sprint->title}\n\n";
        $content .= "Sprint Code: `{$sprint->sprint_code}`\n\n";
        $content .= "## Overview\n\n";
        
        $metadata = $sprint->metadata ?? [];
        if (isset($metadata['goal'])) {
            $content .= "{$metadata['goal']}\n\n";
        }
        
        $content .= "## Status: {$sprint->status}\n\n";
        
        $content .= "## Tasks\n\n";
        foreach ($sprint->tasks as $task) {
            $content .= "### {$task->title}\n";
            $content .= "- **Code**: `{$task->task_code}`\n";
            $content .= "- **Status**: {$task->status}\n";
            $content .= "- **Priority**: {$task->priority}\n\n";
        }
        
        return $content;
    }

    private function generateTaskMarkdown(OrchestrationTask $task): string
    {
        $metadata = $task->metadata ?? [];
        
        $content = "# Task: {$task->title}\n\n";
        $content .= "**Task ID**: `{$task->task_code}`  \n";
        
        if ($task->sprint) {
            $content .= "**Sprint**: `{$task->sprint->sprint_code}`  \n";
        }
        
        $content .= "**Status**: {$task->status}  \n";
        $content .= "**Priority**: {$task->priority}  \n";
        
        if (isset($metadata['phase'])) {
            $content .= "**Phase**: {$metadata['phase']}  \n";
        }
        
        $content .= "\n---\n\n";
        
        if (isset($metadata['objective'])) {
            $content .= "## Objective\n\n{$metadata['objective']}\n\n---\n\n";
        }
        
        if (isset($metadata['context'])) {
            $content .= "## Context\n\n{$metadata['context']}\n\n---\n\n";
        }
        
        if (isset($metadata['deliverables']) && is_array($metadata['deliverables'])) {
            $content .= "## Deliverables\n\n";
            foreach ($metadata['deliverables'] as $deliverable) {
                $content .= "- {$deliverable}\n";
            }
            $content .= "\n---\n\n";
        }
        
        if (isset($metadata['acceptance_criteria']) && is_array($metadata['acceptance_criteria'])) {
            $content .= "## Acceptance Criteria\n\n";
            foreach ($metadata['acceptance_criteria'] as $criterion) {
                $content .= "- ✅ {$criterion}\n";
            }
            $content .= "\n---\n\n";
        }
        
        $content .= "**Task Hash**: `{$task->hash}`  \n";
        $content .= "**Last Updated**: {$task->updated_at->toIso8601String()}  \n";
        
        return $content;
    }
}
