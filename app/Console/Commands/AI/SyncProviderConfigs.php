<?php

namespace App\Console\Commands\AI;

use App\Models\Provider;
use Illuminate\Console\Command;

class SyncProviderConfigs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:providers:sync 
                           {--force : Force sync even if config already exists}
                           {--provider= : Sync only specific provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[DEPRECATED] Use "sync:providers-and-models" instead. Sync provider configurations from fragments.php config';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('⚠️  This command is deprecated!');
        $this->warn('The system now uses database-driven providers synced from models.dev API.');
        $this->info('Please use: php artisan sync:providers-and-models');
        $this->newLine();
        
        if ($this->confirm('Do you want to run the new sync command instead?')) {
            return $this->call('sync:providers-and-models');
        }
        
        $this->info('Command cancelled. Use "php artisan sync:providers-and-models" to sync providers.');
        return 0;
    }
}
