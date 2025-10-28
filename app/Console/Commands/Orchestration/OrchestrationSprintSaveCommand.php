<?php

namespace App\Console\Commands\Orchestration;

use App\Commands\Orchestration\Sprint\SaveCommand;
use Illuminate\Console\Command;

class OrchestrationSprintSaveCommand extends Command
{
    protected $signature = 'orchestration:sprint:save
        {code : Sprint code (e.g. SPRINT-62) or number}
        {--title= : Sprint title}
        {--priority= : Priority label}
        {--status= : Status label}
        {--estimate= : Estimate text}
        {--note=* : Notes (repeatable)}
        {--starts-on= : Start date (Y-m-d)}
        {--ends-on= : End date (Y-m-d)}
        {--meta=* : Additional metadata key=value}
        {--no-upsert : Fail if sprint already exists}
        {--json : Output JSON instead of tables}';

    protected $description = 'Create or update sprint metadata.';

    public function handle(): int
    {
        $notes = $this->option('note');
        if ($notes !== []) {
            $notes = array_values(array_filter($notes));
        }

        $metaPairs = $this->option('meta');
        $meta = null;
        if ($metaPairs !== []) {
            $meta = [];
            foreach ($metaPairs as $pair) {
                if (! str_contains($pair, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $pair, 2);
                $meta[trim($key)] = trim($value);
            }
            if ($meta === []) {
                $meta = null;
            }
        }

        $command = new SaveCommand([
            'code' => $this->argument('code'),
            'title' => $this->option('title'),
            'priority' => $this->option('priority'),
            'status' => $this->option('status'),
            'estimate' => $this->option('estimate'),
            'starts_on' => $this->option('starts-on'),
            'ends_on' => $this->option('ends-on'),
            'notes' => $notes ?: null,
            'meta' => $meta,
            'upsert' => ! $this->option('no-upsert'),
        ]);

        $command->setContext('cli');
        $result = $command->handle();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info(sprintf('Sprint %s saved.', $result['data']['code'] ?? $this->argument('code')));

        return self::SUCCESS;
    }
}
