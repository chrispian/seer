<?php

namespace App\Console\Commands;

use App\Models\WorkItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportTaskContentCommand extends Command
{
    protected $signature = 'delegation:import-content
        {--dry-run : Preview changes without writing to database}
        {--force : Overwrite existing content}
        {--path=delegation : Base delegation directory}';

    protected $description = 'Import task content from delegation markdown files into work_items content fields';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $basePath = base_path($this->option('path'));

        if (! File::isDirectory($basePath)) {
            $this->error("Delegation path not found: {$basePath}");

            return self::FAILURE;
        }

        $this->info('Starting task content import...');
        $this->info('Dry run: '.($dryRun ? 'YES' : 'NO'));
        $this->info('Force overwrite: '.($force ? 'YES' : 'NO'));
        $this->newLine();

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'missing_files' => 0,
        ];

        // Get all work items with source paths
        $tasks = WorkItem::whereNotNull('metadata->source_path')->get();

        $this->info("Found {$tasks->count()} tasks with source paths");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($tasks->count());
        $progressBar->start();

        foreach ($tasks as $task) {
            $stats['processed']++;
            $sourcePathRaw = $task->metadata['source_path'];

            // Try multiple possible locations
            $possiblePaths = [
                base_path($sourcePathRaw),
                base_path('delegation/imported/'.basename($sourcePathRaw)),
                base_path('delegation/archived/'.basename($sourcePathRaw)),
                base_path('delegation/backlog/'.basename($sourcePathRaw)),
            ];

            // If source_path contains a sprint folder, try imported/archived locations
            if (preg_match('/sprint-(\d+)/', $sourcePathRaw, $matches)) {
                $sprintFolder = 'sprint-'.$matches[1];
                $taskFolder = basename($sourcePathRaw);
                $possiblePaths[] = base_path("delegation/imported/{$sprintFolder}/{$taskFolder}");
                $possiblePaths[] = base_path("delegation/archived/{$sprintFolder}/{$taskFolder}");
            }

            $sourcePath = null;
            foreach ($possiblePaths as $path) {
                if (File::isDirectory($path)) {
                    $sourcePath = $path;
                    break;
                }
            }

            if (! $sourcePath) {
                $stats['missing_files']++;
                $progressBar->advance();

                continue;
            }

            $updated = false;
            $contentMap = [
                'agent_content' => 'AGENT.md',
                'plan_content' => 'PLAN.md',
                'context_content' => 'CONTEXT.md',
                'todo_content' => 'TODO.md',
                'summary_content' => 'IMPLEMENTATION_SUMMARY.md',
            ];

            foreach ($contentMap as $field => $filename) {
                $filePath = $sourcePath.'/'.$filename;

                if (! File::exists($filePath)) {
                    continue;
                }

                // Skip if content exists and not forcing
                if (! empty($task->{$field}) && ! $force) {
                    continue;
                }

                $content = File::get($filePath);

                if (! $dryRun) {
                    $task->{$field} = $content;
                    $updated = true;
                }
            }

            if ($updated && ! $dryRun) {
                $task->save();
                $stats['updated']++;
            } elseif (! $updated) {
                $stats['skipped']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Import complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Updated', $stats['updated']],
                ['Skipped (has content)', $stats['skipped']],
                ['Missing source files', $stats['missing_files']],
            ]
        );

        return self::SUCCESS;
    }
}
