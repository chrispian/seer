<?php

namespace App\Console\Commands\Ingest;

use App\Models\TaskActivity;
use App\Models\WorkItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportDelegationContent extends Command
{
    protected $signature = 'orchestration:import-delegation
                            {--dry-run : Show what would be imported without saving}
                            {--overwrite : Overwrite existing content}
                            {--path= : Specific delegation path to import}';

    protected $description = 'Import delegation folder markdown files into work_items content fields';

    protected array $fileToFieldMap = [
        'AGENT.md' => 'agent_content',
        'PLAN.md' => 'plan_content',
        'CONTEXT.md' => 'context_content',
        'TODO.md' => 'todo_content',
        'TASK.md' => null, // Parse for all fields
    ];

    public function handle(): int
    {
        $delegationPath = base_path('delegation');
        $specificPath = $this->option('path');
        $dryRun = $this->option('dry-run');
        $overwrite = $this->option('overwrite');

        if ($specificPath) {
            $delegationPath = base_path($specificPath);
        }

        if (! File::isDirectory($delegationPath)) {
            $this->error("Directory not found: {$delegationPath}");

            return 1;
        }

        $this->info("Scanning delegation folder: {$delegationPath}");
        $this->info($dryRun ? '[DRY RUN MODE]' : '[IMPORT MODE]');
        $this->newLine();

        $taskDirs = $this->findTaskDirectories($delegationPath);

        if (empty($taskDirs)) {
            $this->warn('No task directories found');

            return 0;
        }

        $this->info(sprintf('Found %d task directories', count($taskDirs)));
        $this->newLine();

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($taskDirs as $taskDir) {
            $result = $this->importTaskDirectory($taskDir, $dryRun, $overwrite);

            if ($result === 'imported') {
                $imported++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $errors++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Import complete: %d imported, %d skipped, %d errors',
            $imported,
            $skipped,
            $errors
        ));

        return 0;
    }

    protected function findTaskDirectories(string $basePath): array
    {
        $directories = [];

        foreach (File::directories($basePath) as $dir) {
            $dirname = basename($dir);

            if (preg_match('/^T-[A-Z]+-\d+/', $dirname)) {
                $directories[] = $dir;
            }

            if (in_array($dirname, ['sprints', 'sprint-67', 'sprint-pagination'])) {
                $subdirs = $this->findTaskDirectories($dir);
                $directories = array_merge($directories, $subdirs);
            }
        }

        return $directories;
    }

    protected function importTaskDirectory(string $taskDir, bool $dryRun, bool $overwrite): string
    {
        $taskCode = basename($taskDir);

        $this->line(sprintf('Processing: %s', $taskCode));

        $task = WorkItem::where('metadata->task_code', $taskCode)->first();

        if (! $task) {
            $this->warn("  Task not found in database: {$taskCode}");

            return 'skipped';
        }

        $hasExistingContent = $this->hasExistingContent($task);

        if ($hasExistingContent && ! $overwrite) {
            $this->comment('  Skipping (has content, use --overwrite to replace)');

            return 'skipped';
        }

        $content = $this->parseTaskDirectory($taskDir);

        if (empty(array_filter($content))) {
            $this->comment('  No markdown files found');

            return 'skipped';
        }

        if ($dryRun) {
            $this->info('  Would import:');
            foreach ($content as $field => $text) {
                if ($text) {
                    $this->line(sprintf('    - %s: %d bytes', $field, strlen($text)));
                }
            }

            return 'imported';
        }

        try {
            foreach ($content as $field => $text) {
                if ($text) {
                    $task->{$field} = $text;
                }
            }

            if (! isset($task->metadata['imported_from_delegation'])) {
                $metadata = $task->metadata ?? [];
                $metadata['imported_from_delegation'] = true;
                $metadata['imported_at'] = now()->toIso8601String();
                $metadata['delegation_path'] = str_replace(base_path(), '', $taskDir);
                $task->metadata = $metadata;
            }

            $task->save();

            TaskActivity::logNote(
                taskId: $task->id,
                description: "Imported content from delegation folder: {$taskCode}",
                action: 'content_imported',
                metadata: [
                    'delegation_path' => str_replace(base_path(), '', $taskDir),
                    'fields_imported' => array_keys(array_filter($content)),
                ]
            );

            $this->info('  ✓ Imported successfully');

            return 'imported';
        } catch (\Exception $e) {
            $this->error(sprintf('  ✗ Error: %s', $e->getMessage()));

            return 'error';
        }
    }

    protected function hasExistingContent(WorkItem $task): bool
    {
        return $task->agent_content
            || $task->plan_content
            || $task->context_content
            || $task->todo_content;
    }

    protected function parseTaskDirectory(string $taskDir): array
    {
        $content = [
            'agent_content' => null,
            'plan_content' => null,
            'context_content' => null,
            'todo_content' => null,
        ];

        foreach ($this->fileToFieldMap as $filename => $field) {
            $filepath = $taskDir.'/'.$filename;

            if (! File::exists($filepath)) {
                continue;
            }

            $fileContent = File::get($filepath);

            if ($field) {
                $content[$field] = $fileContent;
            } else {
                $this->parseTaskFile($fileContent, $content);
            }
        }

        return $content;
    }

    protected function parseTaskFile(string $fileContent, array &$content): void
    {
        $sections = $this->extractMarkdownSections($fileContent);

        foreach ($sections as $heading => $text) {
            $headingLower = strtolower($heading);

            if (str_contains($headingLower, 'agent') || str_contains($headingLower, 'profile')) {
                $content['agent_content'] = ($content['agent_content'] ?? '')."\n\n## {$heading}\n\n{$text}";
            } elseif (str_contains($headingLower, 'plan') || str_contains($headingLower, 'implementation')) {
                $content['plan_content'] = ($content['plan_content'] ?? '')."\n\n## {$heading}\n\n{$text}";
            } elseif (str_contains($headingLower, 'context') || str_contains($headingLower, 'background')) {
                $content['context_content'] = ($content['context_content'] ?? '')."\n\n## {$heading}\n\n{$text}";
            } elseif (str_contains($headingLower, 'todo') || str_contains($headingLower, 'checklist') || str_contains($headingLower, 'acceptance')) {
                $content['todo_content'] = ($content['todo_content'] ?? '')."\n\n## {$heading}\n\n{$text}";
            }
        }
    }

    protected function extractMarkdownSections(string $content): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        $currentHeading = 'Content';
        $currentContent = [];

        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                if ($currentContent) {
                    $sections[$currentHeading] = implode("\n", $currentContent);
                }
                $currentHeading = $matches[1];
                $currentContent = [];
            } else {
                $currentContent[] = $line;
            }
        }

        if ($currentContent) {
            $sections[$currentHeading] = implode("\n", $currentContent);
        }

        return $sections;
    }
}
