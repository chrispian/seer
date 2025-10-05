<?php

namespace App\Console\Commands;

use App\Models\Sprint;
use App\Models\WorkItem;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrchestrationSprintsCommand extends Command
{
    protected $signature = 'orchestration:sprints
        {--code=* : Limit to specific sprint codes or numbers}
        {--limit=10 : Maximum number of sprints to display}
        {--details : Include representative tasks (limited set)}
        {--tasks-limit=5 : Number of tasks to include when using --details}
        {--json : Output JSON instead of a table}';

    protected $description = 'Summarise orchestration sprints and their work item progress.';

    public function handle(): int
    {
        $codes = $this->normaliseCodes($this->option('code'));
        $limit = $this->normalisePositiveInt($this->option('limit')) ?? 10;
        $details = (bool) $this->option('details');
        $tasksLimit = $details ? ($this->normalisePositiveInt($this->option('tasks-limit')) ?? 5) : 0;

        $sprints = Sprint::query()
            ->when($codes, fn ($query) => $query->whereIn('code', $codes))
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        $summary = $sprints->map(fn ($sprint) => $this->summariseSprint($sprint, $details, $tasksLimit));

        $isJson = (bool) $this->option('json');

        if ($isJson) {
            $this->line(json_encode([
                'data' => $summary,
                'meta' => [
                    'count' => $summary->count(),
                ],
            ], JSON_PRETTY_PRINT));
        } else {
            $this->outputTable($summary);
        }

        if (! $isJson) {
            $this->info(sprintf('Total sprints shown: %d', $summary->count()));
        }

        return self::SUCCESS;
    }

    private function summariseSprint(Sprint $sprint, bool $withTasks, int $tasksLimit): array
    {
        $taskQuery = WorkItem::query()->where('metadata->sprint_code', $sprint->code);

        $total = (clone $taskQuery)->count();
        $completed = (clone $taskQuery)->where('delegation_status', 'completed')->count();
        $inProgress = (clone $taskQuery)->whereIn('delegation_status', ['assigned', 'in_progress'])->count();
        $blocked = (clone $taskQuery)->where('delegation_status', 'blocked')->count();
        $unassigned = (clone $taskQuery)->where('delegation_status', 'unassigned')->count();

        $tasks = [];

        if ($withTasks && $total > 0) {
            $tasks = (clone $taskQuery)
                ->orderBy('created_at', 'desc')
                ->limit($tasksLimit)
                ->get()
                ->map(fn (WorkItem $item) => [
                    'task_code' => Arr::get($item->metadata, 'task_code'),
                    'delegation_status' => $item->delegation_status,
                    'status' => $item->status,
                    'agent_recommendation' => Arr::get($item->delegation_context, 'agent_recommendation'),
                    'estimate_text' => Arr::get($item->metadata, 'estimate_text'),
                ])
                ->values()
                ->all();
        }

        $meta = $sprint->meta ?? [];

        return [
            'code' => $sprint->code,
            'title' => Arr::get($meta, 'title', $sprint->code),
            'priority' => Arr::get($meta, 'priority'),
            'estimate' => Arr::get($meta, 'estimate'),
            'notes' => Arr::get($meta, 'notes', []),
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'blocked' => $blocked,
                'unassigned' => $unassigned,
            ],
            'tasks' => $tasks,
        ];
    }

    private function outputTable(Collection $summary): void
    {
        if ($summary->isEmpty()) {
            $this->warn('No sprints found for the provided filters.');

            return;
        }

        $this->table(
            ['Sprint', 'Title', 'Priority', 'Estimate', 'Total', 'Completed', 'In Progress', 'Blocked', 'Unassigned'],
            $summary->map(fn (array $row) => [
                $row['code'],
                Str::limit((string) $row['title'], 50),
                $row['priority'] ?? '—',
                $row['estimate'] ?? '—',
                $row['stats']['total'],
                $row['stats']['completed'],
                $row['stats']['in_progress'],
                $row['stats']['blocked'],
                $row['stats']['unassigned'],
            ])->toArray()
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

    private function normalisePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
