<?php

namespace App\Console\Commands;

use App\Jobs\SyncOllamaLocalModels;
use Illuminate\Console\Command;

class SyncOllamaLocalModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ollama-local {--queue : Run sync job in queue instead of synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync models from local Ollama installation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting sync of local Ollama models...');

        if ($this->option('queue')) {
            SyncOllamaLocalModels::dispatch();
            $this->info('Sync job has been queued.');
        } else {
            try {
                (new SyncOllamaLocalModels())->handle();
                $this->info('✅ Sync completed successfully!');
                
                // Show stats
                $provider = \App\Models\Provider::where('provider', 'ollama-local')->first();
                if ($provider) {
                    $modelCount = \App\Models\AIModel::where('provider_id', $provider->id)->count();
                    $this->table(
                        ['Provider', 'Models'],
                        [
                            ['Ollama (Local)', $modelCount],
                        ]
                    );
                }
                
            } catch (\Exception $e) {
                $this->error('❌ Sync failed: ' . $e->getMessage());
                
                if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'Connection timed out')) {
                    $this->warn('💡 Make sure Ollama is running: ollama serve');
                }
                
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}