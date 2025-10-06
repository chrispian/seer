<?php

namespace App\Console\Commands;

use App\Models\WorkItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportBacklogItemsCommand extends Command
{
    protected $signature = 'delegation:import-backlog
        {--dry-run : Preview results without writing to the database}
        {--status=done : Status to assign to imported backlog items}';

    protected $description = 'Import backlog items and mark them as completed tasks';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $status = $this->option('status');

        $this->info(sprintf(
            'Importing backlog items%s with status: %s',
            $dryRun ? ' (dry-run)' : '',
            $status
        ));

        $backlogPath = base_path('delegation/backlog');

        if (! File::isDirectory($backlogPath)) {
            $this->error("Backlog directory not found: {$backlogPath}");

            return self::FAILURE;
        }

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($backlogPath, $status, $dryRun, &$summary) {
            $this->processBacklogDirectory($backlogPath, $status, $dryRun, $summary);
        });

        $this->displayResults($summary, $dryRun);

        return self::SUCCESS;
    }

    private function processBacklogDirectory(string $backlogPath, string $status, bool $dryRun, array &$summary): void
    {
        $entries = File::directories($backlogPath);

        foreach ($entries as $taskDir) {
            $taskName = basename($taskDir);

            // Skip special files
            if (in_array($taskName, ['.DS_Store', 'assets'])) {
                continue;
            }

            $this->processBacklogTask($taskDir, $taskName, $status, $dryRun, $summary);
        }

        // Also process any standalone markdown files
        $markdownFiles = File::glob($backlogPath.'/*.md');
        foreach ($markdownFiles as $file) {
            if (basename($file) !== 'kanban.md') {
                $this->processStandaloneBacklogFile($file, $status, $dryRun, $summary);
            }
        }
    }

    private function processBacklogTask(string $taskDir, string $taskName, string $status, bool $dryRun, array &$summary): void
    {
        $summary['processed']++;

        // Generate task code from directory name
        $taskCode = $this->generateTaskCode($taskName);

        // Check for nested directories (like demo-seeder-pack/demo-seeder-pack)
        $nestedDirs = File::directories($taskDir);
        if (! empty($nestedDirs)) {
            foreach ($nestedDirs as $nestedDir) {
                $this->processBacklogTask($nestedDir, basename($nestedDir), $status, $dryRun, $summary);
            }

            return;
        }

        // Read content files
        $agentContent = $this->readFileIfExists($taskDir.'/AGENT.md');
        $planContent = $this->readFileIfExists($taskDir.'/PLAN.md');
        $contextContent = $this->readFileIfExists($taskDir.'/CONTEXT.md');
        $todoContent = $this->readFileIfExists($taskDir.'/TODO.md');
        $summaryContent = $this->readFileIfExists($taskDir.'/IMPLEMENTATION_SUMMARY.md');

        // Extract title from AGENT.md or use directory name
        $title = $this->extractTitleFromContent($agentContent) ?: Str::headline($taskName);

        $workItemData = [
            'type' => 'task',
            'status' => $status,
            'priority' => 'medium',
            'tags' => ['backlog', 'imported'],
            'delegation_status' => $status === 'done' ? 'completed' : 'unassigned',
            'delegation_context' => [
                'status_text' => $status,
                'source' => 'backlog',
                'imported_from' => 'delegation/backlog',
            ],
            'metadata' => [
                'task_code' => $taskCode,
                'task_name' => $title,
                'description' => $title,
                'source_path' => Str::after($taskDir, base_path().'/'),
                'backlog_import' => true,
            ],
            'state' => [
                'source' => 'backlog',
                'task_code' => $taskCode,
            ],
            'agent_content' => $agentContent,
            'plan_content' => $planContent,
            'context_content' => $contextContent,
            'todo_content' => $todoContent,
            'summary_content' => $summaryContent,
            'completed_at' => $status === 'done' ? now() : null,
        ];

        if ($dryRun) {
            $this->info("Would create: {$taskCode} - {$title}");
            $summary['created']++;

            return;
        }

        // Check if already exists
        $existing = WorkItem::where('metadata->task_code', $taskCode)->first();

        if ($existing) {
            $existing->update($workItemData);
            $summary['updated']++;
            $this->line("Updated: {$taskCode} - {$title}");
        } else {
            WorkItem::create($workItemData);
            $summary['created']++;
            $this->line("Created: {$taskCode} - {$title}");
        }
    }

    private function processStandaloneBacklogFile(string $filePath, string $status, bool $dryRun, array &$summary): void
    {
        $summary['processed']++;

        $fileName = basename($filePath, '.md');
        $taskCode = $this->generateTaskCode($fileName);

        $content = File::get($filePath);
        $title = $this->extractTitleFromContent($content) ?: Str::headline($fileName);

        $workItemData = [
            'type' => 'task',
            'status' => $status,
            'priority' => 'low',
            'tags' => ['backlog', 'imported', 'standalone'],
            'delegation_status' => $status === 'done' ? 'completed' : 'unassigned',
            'delegation_context' => [
                'status_text' => $status,
                'source' => 'backlog_file',
                'imported_from' => Str::after($filePath, base_path().'/'),
            ],
            'metadata' => [
                'task_code' => $taskCode,
                'task_name' => $title,
                'description' => $title,
                'source_path' => Str::after($filePath, base_path().'/'),
                'backlog_import' => true,
            ],
            'state' => [
                'source' => 'backlog_file',
                'task_code' => $taskCode,
            ],
            'context_content' => $content,
            'completed_at' => $status === 'done' ? now() : null,
        ];

        if ($dryRun) {
            $this->info("Would create standalone: {$taskCode} - {$title}");
            $summary['created']++;

            return;
        }

        // Check if already exists
        $existing = WorkItem::where('metadata->task_code', $taskCode)->first();

        if ($existing) {
            $existing->update($workItemData);
            $summary['updated']++;
            $this->line("Updated standalone: {$taskCode} - {$title}");
        } else {
            WorkItem::create($workItemData);
            $summary['created']++;
            $this->line("Created standalone: {$taskCode} - {$title}");
        }
    }

    private function generateTaskCode(string $name): string
    {
        // Try to extract existing task code pattern
        if (preg_match('/^([A-Z]+-[0-9]+)/', $name, $matches)) {
            return $matches[1];
        }

        // Generate new code based on name
        $prefix = 'BACKLOG';
        $hash = substr(md5($name), 0, 6);

        return "{$prefix}-{$hash}";
    }

    private function extractTitleFromContent(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        // Look for first markdown header
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function readFileIfExists(string $path): ?string
    {
        return File::exists($path) ? File::get($path) : null;
    }

    private function displayResults(array $summary, bool $dryRun): void
    {
        $this->line('');
        $this->info('Backlog Import Results:');
        $this->line("Items processed: {$summary['processed']}");
        $this->line("Items created: {$summary['created']}");
        $this->line("Items updated: {$summary['updated']}");

        if ($dryRun) {
            $this->warn('This was a dry run - no data was actually imported');
        }

        if (! empty($summary['errors'])) {
            $this->error('Errors encountered:');
            foreach ($summary['errors'] as $error) {
                $this->error(" - {$error}");
            }
        }
    }
}
