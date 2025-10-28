<?php

namespace App\Console\Commands\Orchestration;

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Models\SprintItem;
use App\Models\WorkItem;
use Illuminate\Console\Command;

class MigrateWorkItemsToOrchestration extends Command
{
    protected $signature = 'migrate:work-items-to-orchestration {--dry-run : Run without making changes}';

    protected $description = 'Migrate data from work_items table to orchestration_tasks table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $workItems = WorkItem::all();
        $this->info("Found {$workItems->count()} work items to migrate");

        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        $idMap = [];

        foreach ($workItems as $workItem) {
            $taskCode = "TASK-WI-{$workItem->id}";
            $existing = OrchestrationTask::where('task_code', $taskCode)->first();

            if ($existing) {
                $this->warn("Skipping work item {$workItem->id} - already exists");
                $idMap[$workItem->id] = $existing->id;
                $skipped++;
                continue;
            }

            try {
                $sprintId = null;
                $sprintItem = SprintItem::where('work_item_id', $workItem->id)->first();
                if ($sprintItem) {
                    $sprint = \App\Models\Sprint::find($sprintItem->sprint_id);
                    if ($sprint) {
                        $orchestrationSprint = OrchestrationSprint::where('sprint_code', $sprint->code)->first();
                        if ($orchestrationSprint) {
                            $sprintId = $orchestrationSprint->id;
                        }
                    }
                }

                if (!$dryRun) {
                    $task = OrchestrationTask::create([
                        'sprint_id' => $sprintId,
                        'type' => $workItem->type,
                        'parent_id' => null,
                        'assignee_type' => $workItem->assignee_type,
                        'assignee_id' => $workItem->assignee_id,
                        'project_id' => $workItem->project_id,
                        'task_code' => $taskCode,
                        'title' => "Work Item {$workItem->id}",
                        'status' => $this->mapStatus($workItem->status),
                        'delegation_status' => $workItem->delegation_status ?? 'unassigned',
                        'priority' => $this->mapPriority($workItem->priority),
                        'tags' => $workItem->tags,
                        'state' => $workItem->state,
                        'delegation_context' => $workItem->delegation_context,
                        'delegation_history' => $workItem->delegation_history,
                        'estimated_hours' => $workItem->estimated_hours,
                        'actual_hours' => $workItem->actual_hours,
                        'metadata' => $workItem->metadata,
                        'agent_content' => $workItem->agent_content,
                        'plan_content' => $workItem->plan_content,
                        'context_content' => $workItem->context_content,
                        'todo_content' => $workItem->todo_content,
                        'summary_content' => $workItem->summary_content,
                        'pr_url' => $workItem->pr_url,
                        'completed_at' => $workItem->completed_at,
                        'created_at' => $workItem->created_at,
                        'updated_at' => $workItem->updated_at,
                    ]);

                    $idMap[$workItem->id] = $task->id;
                }

                $this->info("✓ Migrated work item: {$workItem->id}");
                $migrated++;
            } catch (\Exception $e) {
                $this->error("✗ Error migrating work item {$workItem->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        if (!$dryRun && count($idMap) > 0) {
            $this->info("\nUpdating parent relationships...");
            $parentUpdates = 0;

            foreach ($workItems as $workItem) {
                if ($workItem->parent_id && isset($idMap[$workItem->id]) && isset($idMap[$workItem->parent_id])) {
                    OrchestrationTask::where('id', $idMap[$workItem->id])
                        ->update(['parent_id' => $idMap[$workItem->parent_id]]);
                    $parentUpdates++;
                }
            }

            $this->info("Updated {$parentUpdates} parent relationships");
        }

        $this->newLine();
        $this->info("Migration complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Migrated', $migrated],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total', $workItems->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a DRY RUN - no changes were made. Run without --dry-run to apply changes.');
        }

        return $errors > 0 ? 1 : 0;
    }

    private function mapStatus(string $status): string
    {
        return match($status) {
            'backlog', 'todo' => 'pending',
            'in_progress', 'started' => 'in_progress',
            'done', 'completed' => 'completed',
            'blocked' => 'blocked',
            default => 'pending',
        };
    }

    private function mapPriority(?string $priority): string
    {
        if (!$priority) {
            return 'P2';
        }

        return match(strtoupper($priority)) {
            'P0', 'CRITICAL', 'HIGHEST' => 'P0',
            'P1', 'HIGH' => 'P1',
            'P2', 'MEDIUM', 'NORMAL' => 'P2',
            'P3', 'LOW', 'LOWEST' => 'P3',
            default => 'P2',
        };
    }
}
