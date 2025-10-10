<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SafeMigrateRefreshCommand extends Command
{
    protected $signature = 'migrate:refresh {--database= : The database connection to use}
                            {--force : Force the operation to run when in production}
                            {--path=* : The path(s) to the migrations files to be executed}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--seed : Indicates if the seed task should be re-run}
                            {--seeder= : The class name of the root seeder}
                            {--step= : The number of migrations to be reverted & re-run}';

    protected $description = '[DISABLED] This command is disabled to prevent accidental database resets. Use "php artisan migrate" instead.';

    public function handle(): int
    {
        $this->error('❌ COMMAND DISABLED FOR SAFETY');
        $this->newLine();
        $this->warn('The "migrate:refresh" command rolls back all migrations and re-runs them.');
        $this->warn('This is disabled to prevent accidental data loss.');
        $this->newLine();
        $this->info('✅ Safe alternatives:');
        $this->line('  • php artisan migrate              - Run new migrations only (safe)');
        $this->line('  • php artisan migrate:rollback     - Rollback last batch');
        $this->line('  • php artisan migrate --pretend    - Preview migrations without running');
        $this->newLine();
        $this->comment('If you absolutely need to refresh migrations:');
        $this->line('  1. Create a backup first');
        $this->line('  2. Ask the user for explicit permission');
        $this->line('  3. Temporarily disable this guard if needed');
        $this->newLine();

        return self::FAILURE;
    }
}
