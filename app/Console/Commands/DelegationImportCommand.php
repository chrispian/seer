<?php

namespace App\Console\Commands;

use App\Services\DelegationMigrationService;
use Illuminate\Console\Command;

class DelegationImportCommand extends Command
{
    protected $signature = 'delegation:import
        {--sprint=* : Limit import to one or more sprint numbers/codes}
        {--dry-run : Preview results without writing to the database}
        {--path= : Override delegation base directory}';

    protected $description = 'Import delegation sprint/task metadata into orchestration tables';

    public function __construct(private readonly DelegationMigrationService $migration)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sprints = $this->option('sprint');
        $path = $this->option('path');

        $this->info(sprintf(
            'Delegation import %s starting%s...',
            $dryRun ? '(dry-run)' : '',
            $sprints ? ' for '.implode(', ', $sprints) : ''
        ));

        $summary = $this->migration->import([
            'dry_run' => $dryRun,
            'sprint' => $sprints,
            'path' => $path,
        ]);

        $this->line(sprintf(
            'Agents: processed %d (created %d, updated %d, skipped %d)',
            $summary['agents']['processed'],
            $summary['agents']['created'],
            $summary['agents']['updated'],
            $summary['agents']['skipped'],
        ));

        $this->line(sprintf(
            'Sprints: processed %d (created %d, updated %d)',
            $summary['sprints']['processed'],
            $summary['sprints']['created'],
            $summary['sprints']['updated'],
        ));

        $this->line(sprintf(
            'Work items: processed %d (created %d, updated %d)',
            $summary['work_items']['processed'],
            $summary['work_items']['created'],
            $summary['work_items']['updated'],
        ));

        if (! empty($summary['warnings'])) {
            $this->warn('Warnings:');
            foreach ($summary['warnings'] as $warning) {
                $this->warn(' - '.$warning);
            }
        }

        if ($dryRun && ! empty($summary['preview'] ?? [])) {
            $this->line('Preview:');
            foreach ($summary['preview'] as $preview) {
                $this->line(sprintf(' - %s (%s tasks)', $preview['code'], $preview['task_count']));
            }
        }

        $this->info('Delegation import complete.');

        return self::SUCCESS;
    }
}
