<?php

namespace App\Console\Commands;

use App\Models\OrchestrationSprint;
use App\Models\Sprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSprintsToOrchestration extends Command
{
    protected $signature = 'migrate:sprints-to-orchestration {--dry-run : Run without making changes}';

    protected $description = 'Migrate data from sprints table to orchestration_sprints table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $sprints = Sprint::all();
        $this->info("Found {$sprints->count()} sprints to migrate");

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($sprints as $sprint) {
            $existing = OrchestrationSprint::where('sprint_code', $sprint->code)->first();
            
            if ($existing) {
                $this->warn("Skipping sprint {$sprint->code} - already exists");
                $skipped++;
                continue;
            }

            try {
                if (!$dryRun) {
                    OrchestrationSprint::create([
                        'sprint_code' => $sprint->code,
                        'title' => $sprint->code,
                        'status' => 'completed',
                        'starts_on' => $sprint->starts_on,
                        'ends_on' => $sprint->ends_on,
                        'metadata' => $sprint->meta,
                        'created_at' => $sprint->created_at,
                        'updated_at' => $sprint->updated_at,
                    ]);
                }
                
                $this->info("✓ Migrated sprint: {$sprint->code}");
                $migrated++;
            } catch (\Exception $e) {
                $this->error("✗ Error migrating sprint {$sprint->code}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Migration complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Migrated', $migrated],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total', $sprints->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a DRY RUN - no changes were made. Run without --dry-run to apply changes.');
        }

        return $errors > 0 ? 1 : 0;
    }
}
