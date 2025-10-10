<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SafeMigrateFreshCommand extends Command
{
    protected $signature = 'migrate:fresh {--database= : The database connection to use}
                            {--drop-views : Drop all tables and views}
                            {--drop-types : Drop all tables and types}
                            {--force : Force the operation to run when in production}
                            {--path=* : The path(s) to the migrations files to be executed}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--schema-path= : The path to a schema dump file}
                            {--seed : Indicates if the seed task should be re-run}
                            {--seeder= : The class name of the root seeder}
                            {--step : Force the migrations to be run so they can be rolled back individually}';

    protected $description = '[DISABLED] This command is disabled to prevent accidental database wipes. Use "php artisan migrate" instead.';

    public function handle(): int
    {
        $this->error('❌ COMMAND DISABLED FOR SAFETY');
        $this->newLine();
        $this->warn('The "migrate:fresh" command drops ALL tables and is disabled to prevent accidental data loss.');
        $this->newLine();
        $this->info('✅ Safe alternatives:');
        $this->line('  • php artisan migrate              - Run new migrations only (safe)');
        $this->line('  • php artisan migrate:rollback     - Rollback last batch');
        $this->line('  • php artisan migrate --pretend    - Preview migrations without running');
        $this->newLine();
        $this->comment('If you absolutely need to reset the database:');
        $this->line('  1. Create a backup first');
        $this->line('  2. Ask the user for explicit permission');
        $this->line('  3. Temporarily disable this guard if needed');
        $this->newLine();

        return self::FAILURE;
    }
}
