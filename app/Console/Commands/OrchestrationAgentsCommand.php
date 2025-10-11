<?php

namespace App\Console\Commands;

use App\Services\AgentProfileService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * CLI-ONLY Artisan command for listing agent profiles.
 * 
 * IMPORTANT: This is a console command for CLI use only.
 * It is NOT part of the unified command system used by the web UI.
 * 
 * Web UI and MCP use: /agents → App\Commands\Orchestration\Agent\ListCommand
 * CLI console use:    orchestration:agents → This class
 * 
 * Do NOT add this to the commands table or CommandsSeeder.
 */
class OrchestrationAgentsCommand extends Command
{
    protected $signature = 'orchestration:agents
        {--status=* : Filter by agent status (active, inactive, archived)}
        {--type=* : Filter by agent type slug}
        {--mode=* : Filter by agent mode}
        {--search= : Match name or slug}
        {--limit=20 : Maximum number of agents to display}
        {--json : Output JSON instead of a table}';

    protected $description = 'List orchestration agent profiles with optional filters.';

    public function __construct(private readonly AgentProfileService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $filters = [
            'status' => $this->option('status') ?: null,
            'type' => $this->option('type') ?: null,
            'mode' => $this->option('mode') ?: null,
            'search' => $this->option('search') ?: null,
            'limit' => $this->normaliseLimit($this->option('limit')),
        ];

        $agents = $this->service->list(array_filter($filters, static function ($value) {
            if (is_array($value)) {
                return ! empty(array_filter($value));
            }

            return $value !== null && $value !== '';
        }));

        $isJson = (bool) $this->option('json');

        if ($isJson) {
            $this->outputJson($agents);
        } else {
            $this->outputTable($agents);
        }

        if (! $isJson) {
            $this->info(sprintf('Total agents shown: %d', $agents->count()));
        }

        return self::SUCCESS;
    }

    private function outputJson(Collection $agents): void
    {
        $payload = [
            'data' => $agents->map(fn ($agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
                'type' => $agent->type?->value,
                'mode' => $agent->mode?->value,
                'status' => $agent->status?->value,
                'capabilities' => $agent->capabilities ?? [],
                'constraints' => $agent->constraints ?? [],
                'tools' => $agent->tools ?? [],
                'updated_at' => optional($agent->updated_at)->toIso8601String(),
            ])->values(),
            'meta' => [
                'count' => $agents->count(),
            ],
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT));
    }

    private function outputTable(Collection $agents): void
    {
        if ($agents->isEmpty()) {
            $this->warn('No agents found for the provided filters.');

            return;
        }

        $this->table(
            ['Slug', 'Type', 'Mode', 'Status', 'Capabilities', 'Updated'],
            $agents->map(function ($agent) {
                return [
                    $agent->slug,
                    $agent->type?->value,
                    $agent->mode?->value,
                    $agent->status?->value,
                    $this->formatList($agent->capabilities ?? []),
                    optional($agent->updated_at)->diffForHumans() ?? '—',
                ];
            })->toArray()
        );
    }

    private function formatList(array $items, int $limit = 3): string
    {
        if ($items === []) {
            return '—';
        }

        $display = array_slice($items, 0, $limit);

        if (count($items) > $limit) {
            $display[] = sprintf('… +%d more', count($items) - $limit);
        }

        return implode(', ', $display);
    }

    private function normaliseLimit(mixed $limit): ?int
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        $value = (int) $limit;

        return $value > 0 ? $value : null;
    }
}
