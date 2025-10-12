<?php

namespace App\Console\Commands;

use App\Models\WorkItem;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * CLI-ONLY Artisan command for listing tasks.
 * 
 * IMPORTANT: This is a console command for CLI use only.
 * It is NOT part of the unified command system used by the web UI.
 * 
 * Web UI and MCP use: /tasks → App\Commands\Orchestration\Task\ListCommand
 * CLI console use:    orchestration:tasks → This class
 * 
 * Do NOT add this to the commands table or CommandsSeeder.
 */
class OrchestrationTasksCommand extends Command
{
    protected $signature = 'orchestration:tasks
        {--sprint=* : Filter by sprint codes or numbers}
        {--delegation-status=* : Filter by delegation status (completed, in_progress, assigned, blocked, unassigned)}
        {--status=* : Filter by work item status}
        {--agent= : Filter by recommended agent slug/name}
        {--search= : Match task code or description text}
        {--limit=20 : Maximum number of tasks to display}
        {--json : Output JSON instead of a table}';

    protected $description = 'List orchestration work items with delegation metadata.';

    public function handle(): int
    {
        $limit = $this->normalisePositiveInt($this->option('limit')) ?? 20;
        $query = WorkItem::query()->whereNotNull('metadata->task_code');

        if ($sprints = $this->normaliseCodes($this->option('sprint'))) {
            $query->whereIn('metadata->sprint_code', $sprints);
        }

        if ($delegationStatuses = $this->normaliseArrayOption($this->option('delegation-status'))) {
            $query->whereIn('delegation_status', $delegationStatuses);
        }

        if ($statuses = $this->normaliseArrayOption($this->option('status'))) {
            $query->whereIn('status', $statuses);
        }

        if ($agent = $this->option('agent')) {
            $query->where('delegation_context->agent_recommendation', $agent);
        }

        if ($search = $this->option('search')) {
            $query->where(function ($inner) use ($search) {
                $like = '%'.$search.'%';
                $inner->where('metadata->task_code', 'like', $like)
                    ->orWhere('metadata->task_name', 'like', $like)
                    ->orWhere('metadata->description', 'like', $like);
            });
        }

        $tasks = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $presented = $tasks->map(fn (WorkItem $item) => $this->presentTask($item));

        $isJson = (bool) $this->option('json');

        if ($isJson) {
            $this->line(json_encode([
                'data' => $presented,
                'meta' => [
                    'count' => $presented->count(),
                ],
            ], JSON_PRETTY_PRINT));
        } else {
            $this->outputTable($presented);
        }

        if (! $isJson) {
            $this->info(sprintf('Total tasks shown: %d', $presented->count()));
        }

        return self::SUCCESS;
    }

    private function presentTask(WorkItem $item): array
    {
        $metadata = $item->metadata ?? [];
        $context = $item->delegation_context ?? [];

        return [
            'task_code' => Arr::get($metadata, 'task_code'),
            'sprint_code' => Arr::get($metadata, 'sprint_code'),
            'delegation_status' => $item->delegation_status,
            'status' => $item->status,
            'agent_recommendation' => Arr::get($context, 'agent_recommendation'),
            'estimate_text' => Arr::get($metadata, 'estimate_text'),
            'todo_progress' => Arr::get($metadata, 'todo_progress', []),
            'updated_at' => optional($item->updated_at)->toIso8601String(),
        ];
    }

    private function outputTable(Collection $tasks): void
    {
        if ($tasks->isEmpty()) {
            $this->warn('No tasks found for the provided filters.');

            return;
        }

        $this->table(
            ['Task', 'Sprint', 'Delegation', 'Status', 'Agent', 'Estimate', 'Progress'],
            $tasks->map(function (array $task) {
                $progress = $task['todo_progress'];
                $progressLabel = isset($progress['total']) && $progress['total'] > 0
                    ? sprintf('%d/%d', $progress['completed'], $progress['total'])
                    : '—';

                return [
                    $task['task_code'],
                    $task['sprint_code'],
                    $task['delegation_status'],
                    $task['status'],
                    $task['agent_recommendation'] ?? '—',
                    $task['estimate_text'] ?? '—',
                    $progressLabel,
                ];
            })->toArray()
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

    private function normaliseArrayOption(?array $values): ?array
    {
        if (empty($values)) {
            return null;
        }

        $filtered = array_values(array_filter(array_map(static function ($value) {
            $value = trim((string) $value);

            return $value === '' ? null : Str::lower($value);
        }, $values)));

        return $filtered === [] ? null : $filtered;
    }

    private function normalisePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
