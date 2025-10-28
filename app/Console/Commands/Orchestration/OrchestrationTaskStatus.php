<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\OrchestrationPMToolsService;
use Illuminate\Console\Command;

class OrchestrationTaskStatus extends Command
{
    protected $signature = 'orchestration:task-status
                            {task_code : The task code (e.g., phase-1-api-foundation)}
                            {status : New status (pending, in_progress, completed, blocked)}
                            {--notes= : Optional notes about the status change}
                            {--no-event : Skip event emission}
                            {--no-sync : Skip file sync}';

    protected $description = 'Update task status with event emission and file sync';

    public function handle(OrchestrationPMToolsService $pmToolsService): int
    {
        $taskCode = $this->argument('task_code');
        $status = $this->argument('status');
        $options = [
            'notes' => $this->option('notes'),
            'emit_event' => !$this->option('no-event'),
            'sync_to_file' => !$this->option('no-sync'),
        ];

        $this->info("Updating task status: {$taskCode} → {$status}");

        try {
            $result = $pmToolsService->updateTaskStatus($taskCode, $status, $options);

            $this->info("✓ Task status updated successfully");
            $this->line("  Task: {$result['task_code']}");
            $this->line("  Old Status: {$result['old_status']}");
            $this->line("  New Status: {$result['new_status']}");
            $this->line("  Updated At: {$result['updated_at']}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update task status: {$e->getMessage()}");
            return 1;
        }
    }
}
