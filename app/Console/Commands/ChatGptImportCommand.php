<?php

namespace App\Console\Commands;

use App\Services\ChatImports\ChatGptImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'chatgpt:import', description: 'Import ChatGPT web conversations into chat sessions and fragments.')]
class ChatGptImportCommand extends Command
{
    protected $signature = 'chatgpt:import
        {--path= : Directory containing conversations.json or the file itself}
        {--dry-run : Parse conversations without writing to the database}
        {--pipeline : Trigger post-import pipeline processing (future behaviour)}';

    protected $description = 'Import ChatGPT web conversations into chat sessions and fragments.';

    public function handle(ChatGptImportService $service): int
    {
        $pathOption = $this->option('path');

        if (! $pathOption) {
            $this->error('Please provide --path pointing to the ChatGPT export directory or conversations.json');

            return self::FAILURE;
        }

        if (! File::exists($pathOption)) {
            $this->error("Path does not exist: {$pathOption}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $runPipeline = (bool) $this->option('pipeline');

        try {
            $stats = $service->import($pathOption, $dryRun, $runPipeline);
        } catch (JsonException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->outputStatistics($stats);

        if ($dryRun) {
            $this->warn('Dry-run complete; no data was persisted.');
        } elseif ($runPipeline) {
            $this->info('Pipeline processing was requested; specialised pipeline integration will run in a future iteration.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    private function outputStatistics(array $stats): void
    {
        $this->info('ChatGPT Import Summary');

        $rows = [
            ['Conversations (total)', Arr::get($stats, 'conversations_total', 0)],
            ['Conversations (parsed)', Arr::get($stats, 'conversations_parsed', 0)],
            ['Conversations (skipped)', Arr::get($stats, 'conversations_skipped', 0)],
            ['Sessions created', Arr::get($stats, 'sessions_created', 0)],
            ['Sessions updated', Arr::get($stats, 'sessions_updated', 0)],
            ['Messages imported', Arr::get($stats, 'messages_imported', 0)],
            ['Fragments upserted', Arr::get($stats, 'fragments_upserted', 0)],
        ];

        $this->table(['Metric', 'Value'], $rows);
    }
}
