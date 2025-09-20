<?php

namespace App\Console\Commands;

use App\Jobs\EmbedFragment;
use App\Models\Fragment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmbeddingsBackfill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:backfill
                            {--batch=100 : Number of fragments to process per batch}
                            {--provider= : Override the default embeddings provider}
                            {--model= : Override the default embeddings model}
                            {--dry-run : Show what would be done without executing}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing embeddings for fragments when embeddings are enabled';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('fragments.embeddings.enabled')) {
            $this->error('❌ Embeddings are currently disabled. Set EMBEDDINGS_ENABLED=true to enable backfilling.');

            return Command::FAILURE;
        }

        // Check pgvector support
        if (! $this->hasPgVectorSupport()) {
            $this->error('❌ pgvector extension is not available. Embeddings require PostgreSQL with pgvector.');

            return Command::FAILURE;
        }

        $batchSize = (int) $this->option('batch');
        $provider = $this->option('provider') ?? config('fragments.embeddings.provider');
        $model = $this->option('model') ?? config('fragments.embeddings.model');
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('🔍 Analyzing fragments for missing embeddings...');

        // Find fragments missing embeddings
        $missingEmbeddings = $this->findFragmentsMissingEmbeddings($provider, $model);
        $totalCount = $missingEmbeddings->count();

        if ($totalCount === 0) {
            $this->info('✅ All fragments already have embeddings for provider: '.$provider.', model: '.$model);

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->line('<fg=cyan>📊 BACKFILL SUMMARY</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("Fragments missing embeddings: <fg=yellow>{$totalCount}</fg=yellow>");
        $this->line("Provider: <fg=green>{$provider}</fg=green>");
        $this->line("Model: <fg=green>{$model}</fg=green>");
        $this->line("Batch size: <fg=blue>{$batchSize}</fg=blue>");
        $this->line('Estimated batches: <fg=blue>'.ceil($totalCount / $batchSize).'</fg=blue>');
        $this->newLine();

        if ($isDryRun) {
            $this->info('🔍 DRY RUN: Would queue '.$totalCount.' embedding jobs');

            if ($this->option('verbose')) {
                $this->line('<fg=cyan>Sample fragments that would be processed:</fg=cyan>');
                $missingEmbeddings->take(5)->each(function ($fragment) {
                    $this->line("  ID: {$fragment->id} - ".\Illuminate\Support\Str::limit($fragment->message ?? '', 50));
                });

                if ($totalCount > 5) {
                    $this->line('  ... and '.($totalCount - 5).' more');
                }
            }

            return Command::SUCCESS;
        }

        if (! $isForced && ! $this->confirm("Queue {$totalCount} embedding jobs?")) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->info('🚀 Starting backfill process...');
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $jobsQueued = 0;
        $errors = 0;

        try {
            $missingEmbeddings->chunk($batchSize, function ($fragments) use ($provider, $model, $progressBar, &$jobsQueued, &$errors) {
                foreach ($fragments as $fragment) {
                    try {
                        $text = trim($fragment->edited_message ?? $fragment->message ?? '');
                        if ($text === '') {
                            $progressBar->advance();

                            continue;
                        }

                        $version = (string) config('fragments.embeddings.version', '1');
                        $contentHash = hash('sha256', $text.'|'.$provider.'|'.$model.'|'.$version);

                        dispatch(new EmbedFragment(
                            fragmentId: $fragment->id,
                            provider: $provider,
                            model: $model,
                            contentHash: $contentHash
                        ))->onQueue('embeddings');

                        $jobsQueued++;
                        $progressBar->advance();
                    } catch (\Throwable $e) {
                        $errors++;
                        $progressBar->advance();
                        Log::error('EmbeddingsBackfill: failed to queue job', [
                            'fragment_id' => $fragment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            $progressBar->finish();
            $this->newLine(2);

            $this->info('✅ Backfill completed!');
            $this->line("Jobs queued: <fg=green>{$jobsQueued}</fg=green>");

            if ($errors > 0) {
                $this->line("Errors: <fg=red>{$errors}</fg=red>");
            }

            $this->newLine();
            $this->line('<fg=yellow>💡 Monitor job processing with:</fg=yellow>');
            $this->line('   php artisan queue:work --queue=embeddings');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('❌ Backfill failed: '.$e->getMessage());
            Log::error('EmbeddingsBackfill: process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function findFragmentsMissingEmbeddings(string $provider, string $model)
    {
        return Fragment::query()
            ->whereNotNull('message')
            ->where('message', '!=', '')
            ->whereNotExists(function ($query) use ($provider, $model) {
                $query->select(DB::raw(1))
                    ->from('fragment_embeddings')
                    ->whereColumn('fragment_embeddings.fragment_id', 'fragments.id')
                    ->where('fragment_embeddings.provider', $provider)
                    ->where('fragment_embeddings.model', $model);
            })
            ->orderBy('created_at', 'desc');
    }

    private function hasPgVectorSupport(): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            return false;
        }

        try {
            // Check if pgvector extension is installed
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");

            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
