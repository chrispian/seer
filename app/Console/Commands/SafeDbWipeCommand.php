<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SafeDbWipeCommand extends Command
{
    protected $signature = 'db:wipe {--database= : The database connection to use}
                            {--drop-views : Drop all tables and views}
                            {--drop-types : Drop all tables and types}
                            {--force : Force the operation to run when in production}';

    protected $description = '[DISABLED] This command is disabled to prevent accidental database wipes. Use "php artisan migrate" instead.';

    public function handle(): int
    {
        $this->error('❌ COMMAND DISABLED FOR SAFETY');
        $this->newLine();
        $this->warn('The "db:wipe" command drops ALL database tables and is permanently disabled.');
        $this->warn('This prevents catastrophic data loss from accidental execution.');
        $this->newLine();
        $this->info('✅ Safe alternatives:');
        $this->line('  • php artisan migrate              - Run new migrations only (safe)');
        $this->line('  • php artisan migrate:rollback     - Rollback last batch');
        $this->line('  • php artisan migrate --pretend    - Preview migrations without running');
        $this->newLine();
        $this->comment('If you absolutely need to wipe the database:');
        $this->line('  1. Create a backup first');
        $this->line('  2. Ask the user for explicit permission');
        $this->line('  3. Use database tools directly (not artisan)');
        $this->newLine();

        return self::FAILURE;
    }
}
