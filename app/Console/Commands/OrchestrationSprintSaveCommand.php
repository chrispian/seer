<?php

namespace App\Console\Commands;

use App\Services\SprintOrchestrationService;
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

    public function handle(SprintOrchestrationService $service): int
    {
        $code = $this->argument('code');
        $attributes = array_filter([
            'code' => $code,
            'title' => $this->option('title'),
            'priority' => $this->option('priority'),
            'estimate' => $this->option('estimate'),
            'status' => $this->option('status'),
            'starts_on' => $this->option('starts-on'),
            'ends_on' => $this->option('ends-on'),
        ], static fn ($value) => $value !== null && $value !== '');

        $notes = $this->option('note');
        if ($notes !== []) {
            $attributes['notes'] = array_values(array_filter($notes));
        }

        $metaPairs = $this->option('meta');
        if ($metaPairs !== []) {
            $meta = [];
            foreach ($metaPairs as $pair) {
                if (! str_contains($pair, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $pair, 2);
                $meta[trim($key)] = trim($value);
            }
            if ($meta !== []) {
                $attributes['meta'] = $meta;
            }
        }

        $upsert = ! $this->option('no-upsert');

        $sprint = $service->create($attributes, $upsert);
        $detail = $service->detail($sprint, [
            'tasks_limit' => 10,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('Sprint %s saved.', $detail['sprint']['code'] ?? $code));

        return self::SUCCESS;
    }
}
