<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Sprint\ListCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * CLI-ONLY Artisan command for listing sprints.
 * 
 * IMPORTANT: This is a console command for CLI use only.
 * It is NOT part of the unified command system used by the web UI.
 * 
 * Web UI and MCP use: /sprints → App\Commands\Orchestration\Sprint\ListCommand
 * CLI console use:    orchestration:sprints → This class (thin wrapper)
 * 
 * These are separate by design:
 * - Console commands output formatted text for terminal
 * - Unified commands return structured data for UI/MCP
 * 
 * Do NOT add this to the commands table or CommandsSeeder.
 */
class OrchestrationSprintsCommand extends Command
{
    protected $signature = 'orchestration:sprints
        {--code=* : Limit to specific sprint codes or numbers}
        {--limit=50 : Maximum number of sprints to display}
        {--details : Include representative tasks (limited set)}
        {--tasks-limit=5 : Number of tasks to include when using --details}
        {--json : Output JSON instead of a table}';

    protected $description = 'List orchestration sprints - CLI ONLY (use /sprints for web UI)';

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
