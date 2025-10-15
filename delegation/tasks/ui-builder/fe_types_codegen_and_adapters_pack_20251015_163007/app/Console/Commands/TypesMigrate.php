<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Types\{TypeRegistry, SchemaDiffService, SchemaMigrationRunner};

class TypesMigrate extends Command
{
    protected $signature = 'types:migrate {alias} {--dry-run}';
    protected $description = 'Compute schema diff for a Type and (optionally) apply DB migrations.';

    public function handle(TypeRegistry $registry, SchemaDiffService $diff, SchemaMigrationRunner $runner)
    {
        $alias = $this->argument('alias');
        $schema = $registry->get($alias);
        if (!$schema) {
            $this->error("Type {$alias} not found.");
            return 1;
        }

        $plan = $diff->diff($schema);
        $this->info('Migration plan:');
        foreach ($plan as $step) {
            $this->line('- ' . $step);
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run only — no changes applied.');
            return 0;
        }

        $runner->apply($schema, $plan);
        $this->info('Migration applied.');
        return 0;
    }
}
