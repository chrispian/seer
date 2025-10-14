<?php

namespace App\Console\Commands;

use App\Services\Orchestration\OrchestrationBugService;
use Illuminate\Console\Command;

class OrchestrationBugLog extends Command
{
    protected $signature = 'orchestration:bug-log 
                            {error : The error message}
                            {--task-code= : The task code this bug is related to}
                            {--file= : File path where error occurred}
                            {--line= : Line number where error occurred}
                            {--stack-trace= : Stack trace (optional)}
                            {--context=* : Additional context key=value pairs}';

    protected $description = 'Log a bug with duplicate detection and get recommended actions';

    public function handle(OrchestrationBugService $bugService): int
    {
        $errorMessage = $this->argument('error');
        $taskCode = $this->option('task-code');
        $filePath = $this->option('file');
        $lineNumber = $this->option('line') ? (int) $this->option('line') : null;
        $stackTrace = $this->option('stack-trace');

        $additionalContext = [];
        foreach ($this->option('context') as $contextPair) {
            if (str_contains($contextPair, '=')) {
                [$key, $value] = explode('=', $contextPair, 2);
                $additionalContext[$key] = $value;
            }
        }

        $bugHash = $bugService->hashBug($errorMessage, $filePath, $lineNumber, $stackTrace);

        $this->info("Bug Hash: {$bugHash}");
        $this->newLine();

        $isDuplicate = $bugService->isDuplicate($bugHash);
        
        if ($isDuplicate) {
            $occurrences = $bugService->getOccurrenceCount($bugHash);
            $this->warn("⚠️  This bug has been seen before! ({$occurrences} occurrences)");
            $this->newLine();

            $similar = $bugService->searchSimilar($bugHash, 5);
            
            $this->line("<fg=yellow>Previous Occurrences:</>");
            foreach ($similar as $index => $bug) {
                $this->line("  " . ($index + 1) . ". Task: " . ($bug->task_code ?? 'N/A') . 
                           " at " . $bug->created_at->diffForHumans());
                if ($bug->isResolved()) {
                    $this->line("     <fg=green>✓ Resolved:</> {$bug->resolution}");
                }
            }
            $this->newLine();
        }

        $bug = $bugService->logBug(
            $errorMessage,
            $taskCode,
            $filePath,
            $lineNumber,
            $stackTrace,
            $additionalContext
        );

        $this->info("✓ Bug logged successfully (ID: {$bug->id})");

        if ($filePath) {
            $location = $filePath . ($lineNumber ? ":{$lineNumber}" : '');
            $this->line("  Location: {$location}");
        }

        $this->newLine();

        if ($taskCode) {
            $isRelated = true;
        } else {
            $isRelated = $this->confirm('Is this bug related to your current task?', false);
        }

        $prompt = $bugService->promptUserAction($bug, $isRelated);
        
        $action = $this->choice(
            'What would you like to do?',
            array_values($prompt['options']),
            0
        );

        $actionKey = array_search($action, $prompt['options']);

        switch ($actionKey) {
            case 'fix_now':
                $this->info("→ Bug marked for immediate fixing");
                $this->line("  Context and details are available for investigation");
                break;
            
            case 'log_and_continue':
                $this->info("→ Bug logged for later. Continuing with task...");
                break;
            
            case 'log_only':
                $this->info("→ Bug logged for later review");
                break;
            
            case 'provide_context':
                $additionalNotes = $this->ask('Provide additional context about this bug');
                if ($additionalNotes) {
                    $context = $bug->context ?? [];
                    $context['user_notes'] = $additionalNotes;
                    $bug->update(['context' => $context]);
                    $this->info("✓ Context added to bug report");
                }
                break;
        }

        return 0;
    }
}
