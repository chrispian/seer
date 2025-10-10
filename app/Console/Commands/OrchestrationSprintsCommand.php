<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Sprint\ListCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OrchestrationSprintsCommand extends Command
{
    protected $signature = 'orchestration:sprints
        {--code=* : Limit to specific sprint codes or numbers}
        {--limit=50 : Maximum number of sprints to display}
        {--details : Include representative tasks (limited set)}
        {--tasks-limit=5 : Number of tasks to include when using --details}
        {--json : Output JSON instead of a table}';

    protected $description = 'List orchestration sprints (thin wrapper around unified command)';

    public function handle(): int
    {
        $command = new ListCommand([
            'codes' => $this->normaliseCodes($this->option('code')),
            'limit' => (int) $this->option('limit'),
            'details' => $this->option('details'),
            'tasks_limit' => (int) $this->option('tasks-limit'),
        ]);

        $command->setContext('cli');
        $result = $command->handle();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->outputTable($result['data'] ?? []);
        }

        if (! $this->option('json')) {
            $this->info(sprintf('Total sprints shown: %d', count($result['data'] ?? [])));
        }

        return self::SUCCESS;
    }

    private function outputTable(array $sprints): void
    {
        if (empty($sprints)) {
            $this->warn('No sprints found.');
            return;
        }

        $this->table(
            ['Code', 'Title', 'Priority', 'Total', 'Completed', 'In Progress', 'Blocked'],
            array_map(fn($s) => [
                $s['code'],
                Str::limit($s['title'], 40),
                $s['priority'] ?? '—',
                $s['stats']['total'],
                $s['stats']['completed'],
                $s['stats']['in_progress'],
                $s['stats']['blocked'],
            ], $sprints)
        );
    }

    private function normaliseCodes(?array $codes): ?array
    {
        if (empty($codes)) {
            return null;
        }

        $normalised = [];

        foreach ($codes as $code) {
            $code = trim((string) $code);

            if ($code === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $code)) {
                $normalised[] = 'SPRINT-'.str_pad($code, 2, '0', STR_PAD_LEFT);
                continue;
            }

            if (preg_match('/^(?:sprint-)?(\d+)$/i', $code, $matches)) {
                $normalised[] = 'SPRINT-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                continue;
            }

            $normalised[] = strtoupper($code);
        }

        return $normalised === [] ? null : array_values(array_unique($normalised));
    }
}
