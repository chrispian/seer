<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class OrchestrationGitService
{
    protected bool $enabled;
    protected bool $autoCommit;
    protected string $commitMessageTemplate;

    public function __construct()
    {
        $this->enabled = config('orchestration.git.enabled', true);
        $this->autoCommit = config('orchestration.git.auto_commit', false);
        $this->commitMessageTemplate = config('orchestration.git.commit_message_template', 
            'feat({sprint_code}): {task_title} [TSK-{task_code}]'
        );
    }

    public function captureCurrentCommit(string $taskCode, ?string $phase = null): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $task = OrchestrationTask::where('task_code', $taskCode)->first();
        
        if (!$task) {
            Log::warning('Cannot capture commit: task not found', ['task_code' => $taskCode]);
            return null;
        }

        $hash = $this->getCurrentCommitHash();
        
        if (!$hash) {
            Log::warning('Cannot capture commit: not in git repository');
            return null;
        }

        $this->trackCommit($taskCode, $hash, $phase);

        return $hash;
    }

    public function trackCommit(string $taskCode, string $hash, ?string $phase = null): void
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();
        
        $metadata = $task->metadata ?? [];
        $commits = $metadata['commits'] ?? [];

        $commitData = [
            'hash' => $hash,
            'short_hash' => substr($hash, 0, 7),
            'captured_at' => now()->toIso8601String(),
            'phase' => $phase,
            'message' => $this->getCommitMessage($hash),
            'author' => $this->getCommitAuthor($hash),
        ];

        $commits[] = $commitData;
        $metadata['commits'] = $commits;

        $task->update(['metadata' => $metadata]);

        Log::info('Commit tracked', [
            'task_code' => $taskCode,
            'commit_hash' => substr($hash, 0, 7),
            'phase' => $phase,
        ]);
    }

    public function linkPullRequest(string $taskCode, string $prUrl): void
    {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();
        
        $metadata = $task->metadata ?? [];
        $metadata['pull_request'] = [
            'url' => $prUrl,
            'linked_at' => now()->toIso8601String(),
            'number' => $this->extractPrNumber($prUrl),
        ];

        $task->update(['metadata' => $metadata]);

        Log::info('PR linked to task', [
            'task_code' => $taskCode,
            'pr_url' => $prUrl,
        ]);
    }

    public function generateChangesMarkdown(
        string $taskCode, 
        ?string $sinceCommit = null
    ): string {
        $task = OrchestrationTask::where('task_code', $taskCode)->firstOrFail();

        if (!$sinceCommit) {
            $commits = $task->metadata['commits'] ?? [];
            $sinceCommit = !empty($commits) ? $commits[0]['hash'] : null;
        }

        $diff = $this->getDiffSummary($sinceCommit);
        $filesChanged = $this->getChangedFiles($sinceCommit);

        $markdown = "# Changes Summary\n\n";
        $markdown .= "**Task**: {$task->title} (`{$task->task_code}`)\n";
        $markdown .= "**Date**: " . now()->toDateString() . "\n\n";

        $markdown .= "## Overview\n\n";
        $markdown .= $task->metadata['description'] ?? 'No description provided.';
        $markdown .= "\n\n";

        if ($sinceCommit) {
            $markdown .= "## Changes Since Commit\n\n";
            $markdown .= "Base commit: `{$sinceCommit}`\n\n";
        }

        $markdown .= "## Modified Files\n\n";
        if (!empty($filesChanged)) {
            foreach ($filesChanged as $file) {
                $markdown .= "- `{$file}`\n";
            }
        } else {
            $markdown .= "_No files changed_\n";
        }

        $markdown .= "\n## Diff Summary\n\n";
        if ($diff) {
            $markdown .= "```\n{$diff}\n```\n";
        } else {
            $markdown .= "_No diff available_\n";
        }

        $commits = $task->metadata['commits'] ?? [];
        if (!empty($commits)) {
            $markdown .= "\n## Related Commits\n\n";
            foreach ($commits as $commit) {
                $markdown .= "- `{$commit['short_hash']}` - {$commit['message']}";
                if ($commit['phase']) {
                    $markdown .= " (Phase: {$commit['phase']})";
                }
                $markdown .= "\n";
            }
        }

        return $markdown;
    }

    public function commitChanges(
        string $taskCode, 
        ?string $message = null
    ): ?string {
        if (!$this->enabled || !$this->autoCommit) {
            Log::info('Auto-commit disabled', ['task_code' => $taskCode]);
            return null;
        }

        $task = OrchestrationTask::where('task_code', $taskCode)->first();
        
        if (!$task) {
            return null;
        }

        if (!$message) {
            $message = $this->generateCommitMessage($task);
        }

        $result = Process::run("git add . && git commit -m \"{$message}\"");

        if ($result->successful()) {
            $hash = $this->getCurrentCommitHash();
            $this->trackCommit($taskCode, $hash, 'auto_commit');
            
            Log::info('Auto-commit created', [
                'task_code' => $taskCode,
                'commit_hash' => substr($hash, 0, 7),
            ]);

            return $hash;
        }

        Log::warning('Auto-commit failed', [
            'task_code' => $taskCode,
            'error' => $result->errorOutput(),
        ]);

        return null;
    }

    protected function getCurrentCommitHash(): ?string
    {
        $result = Process::run('git rev-parse HEAD');

        if ($result->successful()) {
            return trim($result->output());
        }

        return null;
    }

    protected function getCommitMessage(string $hash): ?string
    {
        $result = Process::run("git log -1 --format=%s {$hash}");

        if ($result->successful()) {
            return trim($result->output());
        }

        return null;
    }

    protected function getCommitAuthor(string $hash): ?string
    {
        $result = Process::run("git log -1 --format='%an <%ae>' {$hash}");

        if ($result->successful()) {
            return trim($result->output(), "'");
        }

        return null;
    }

    protected function getDiffSummary(?string $sinceCommit): ?string
    {
        if (!$sinceCommit) {
            $result = Process::run('git diff --stat');
        } else {
            $result = Process::run("git diff --stat {$sinceCommit}");
        }

        if ($result->successful()) {
            return trim($result->output());
        }

        return null;
    }

    protected function getChangedFiles(?string $sinceCommit): array
    {
        if (!$sinceCommit) {
            $result = Process::run('git diff --name-only');
        } else {
            $result = Process::run("git diff --name-only {$sinceCommit}");
        }

        if ($result->successful()) {
            $files = explode("\n", trim($result->output()));
            return array_filter($files);
        }

        return [];
    }

    protected function extractPrNumber(string $prUrl): ?int
    {
        if (preg_match('/\/pull\/(\d+)/', $prUrl, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function generateCommitMessage(OrchestrationTask $task): string
    {
        $sprint = $task->sprint;
        
        $replacements = [
            '{sprint_code}' => $sprint ? $sprint->sprint_code : 'no-sprint',
            '{task_code}' => $task->task_code,
            '{task_title}' => $task->title,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->commitMessageTemplate
        );
    }

    public function isGitRepository(): bool
    {
        $result = Process::run('git rev-parse --git-dir');
        return $result->successful();
    }

    public function getBranchName(): ?string
    {
        $result = Process::run('git rev-parse --abbrev-ref HEAD');

        if ($result->successful()) {
            return trim($result->output());
        }

        return null;
    }

    public function getStatus(): array
    {
        $result = Process::run('git status --porcelain');

        if (!$result->successful()) {
            return [];
        }

        $lines = explode("\n", trim($result->output()));
        $status = [
            'modified' => [],
            'added' => [],
            'deleted' => [],
            'untracked' => [],
        ];

        foreach ($lines as $line) {
            if (empty($line)) continue;

            $statusCode = substr($line, 0, 2);
            $file = trim(substr($line, 3));

            if (str_contains($statusCode, 'M')) {
                $status['modified'][] = $file;
            } elseif (str_contains($statusCode, 'A')) {
                $status['added'][] = $file;
            } elseif (str_contains($statusCode, 'D')) {
                $status['deleted'][] = $file;
            } elseif (str_contains($statusCode, '?')) {
                $status['untracked'][] = $file;
            }
        }

        return $status;
    }
}
