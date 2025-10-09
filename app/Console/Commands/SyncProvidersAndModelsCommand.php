<?php

namespace App\Console\Commands;

use App\Jobs\SyncProvidersAndModels;
use Illuminate\Console\Command;

class SyncProvidersAndModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:providers-models {--queue : Run sync job in queue instead of synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync providers and models from models.dev API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting sync of providers and models from models.dev...');

        if ($this->option('queue')) {
            SyncProvidersAndModels::dispatch();
            $this->info('Sync job has been queued.');
        } else {
            try {
                (new SyncProvidersAndModels)->handle();
                $this->info('✅ Sync completed successfully!');

                // Show stats
                $providerCount = \App\Models\Provider::count();
                $modelCount = \App\Models\AIModel::count();

                $this->table(
                    ['Type', 'Count'],
                    [
                        ['Providers', $providerCount],
                        ['Models', $modelCount],
                    ]
                );

            } catch (\Exception $e) {
                $this->error('❌ Sync failed: '.$e->getMessage());

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
