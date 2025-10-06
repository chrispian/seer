<?php

namespace App\Services;

use App\Models\AgentProfile;
use App\Models\Sprint;
use App\Models\SprintItem;
use App\Models\WorkItem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DelegationMigrationService
{
    public function __construct(private readonly AgentProfileService $agentProfiles)
    {
    }

    /**
     * Import delegation sprint/task data into the orchestration database.
     *
     * @param  array{dry_run?:bool, sprint?:string|array<int,string>, path?:string}  $options
     */
    public function import(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $basePath = rtrim($options['path'] ?? base_path('delegation'), '/');
        $filters = $this->normaliseSprintFilter($options['sprint'] ?? null);
        $warnings = [];

        $statusPath = $basePath.'/SPRINT_STATUS.md';
        $statusContent = File::exists($statusPath) ? File::get($statusPath) : '';
        $statusData = $this->parseSprintStatusContent($statusContent);

        $sprints = $this->collectSprints($basePath, $statusData, $filters, $warnings);
        $agents = $this->synchroniseAgentTemplates($basePath.'/agents/templates', $dryRun);

        $summary = [
            'dry_run' => $dryRun,
            'agents' => $agents,
            'sprints' => [
                'processed' => count($sprints),
                'created' => 0,
                'updated' => 0,
            ],
            'work_items' => [
                'processed' => array_sum(array_map(fn ($sprint) => count($sprint['tasks']), $sprints)),
                'created' => 0,
                'updated' => 0,
            ],
            'warnings' => $warnings,
        ];

        if ($dryRun) {
            $summary['preview'] = array_map(static fn ($sprint) => [
                'code' => $sprint['code'],
                'title' => $sprint['title'],
                'task_count' => count($sprint['tasks']),
            ], $sprints);

            return $summary;
        }

        DB::transaction(function () use (&$summary, $sprints) {
            $nowIso = now()->toIso8601String();

            foreach ($sprints as $sprint) {
                [$wasCreated, $model] = $this->persistSprint($sprint);
                $summary['sprints'][$wasCreated ? 'created' : 'updated']++;

                $position = 1;

                foreach ($sprint['tasks'] as $task) {
                    [$itemCreated, $workItem] = $this->persistWorkItem($task, $sprint, $nowIso);
                    $summary['work_items'][$itemCreated ? 'created' : 'updated']++;

                    $this->persistSprintItem($model, $workItem, $position++);
                }
            }
        });

        return $summary;
    }

    /**
     * Parse the delegation sprint status markdown content into a structured array.
     */
    public function parseSprintStatusContent(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        $lines = preg_split('/\r?\n/', $content);
        $currentSprint = null;
        $currentSection = [];
        $result = [];
        $collectingMeta = false;
        $collectingList = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $collectingMeta = false;
                $collectingList = false;
                continue;
            }

            if (Str::startsWith($trimmed, '### **Sprint')) {
                if ($currentSprint && $currentSection) {
                    $result[$currentSprint] = $currentSection;
                }

                $title = (string) Str::of($trimmed)
                    ->after('### **')
                    ->before('**')
                    ->value();

                $number = (string) Str::of($title)
                    ->after('Sprint ')
                    ->before(':')
                    ->trim();

                $code = $this->normaliseSprintCode($number);

                $currentSprint = $code;
                $currentSection = [
                    'code' => $code,
                    'title' => trim(Str::after($title, ': ')),
                    'meta' => [],
                    'tasks' => [],
                    'notes' => [],
                ];
                $collectingMeta = true;
                continue;
            }

            if (! $currentSprint) {
                continue;
            }

            if (Str::startsWith($trimmed, '| Task ID |')) {
                $headers = $this->parseTableHeaders($trimmed);
                continue;
            }

            if (Str::startsWith($trimmed, '|---------')) {
                continue;
            }

            if (Str::startsWith($trimmed, '|')) {
                if (! isset($headers)) {
                    continue;
                }

                $row = $this->parseTableRow($trimmed, $headers);

                if (! empty($row['task_id'])) {
                    $currentSection['tasks'][$row['task_id']] = $row;
                }

                continue;
            }

            if ($collectingMeta && Str::contains($trimmed, '**')) {
                $meta = $this->parseMetaLine($trimmed);
                $currentSection['meta'] = array_merge($currentSection['meta'], $meta);

                if (Str::startsWith($trimmed, '**Business Goals**')) {
                    $collectingList = true;
                    $currentSection['notes'][] = Str::after($trimmed, '**Business Goals**:');
                }

                continue;
            }

            if ($collectingList && Str::startsWith($trimmed, '- ')) {
                $currentSection['notes'][] = Str::after($trimmed, '- ');
                continue;
            }

            $currentSection['notes'][] = $trimmed;
        }

        if ($currentSprint && $currentSection) {
            $result[$currentSprint] = $currentSection;
        }

        return $result;
    }

    /**
     * Synchronise agent templates into agent_profiles table.
     */
    private function synchroniseAgentTemplates(string $templatePath, bool $dryRun): array
    {
        if (! File::isDirectory($templatePath)) {
            return ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $summary = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach (File::files($templatePath) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $summary['processed']++;
            $filename = $file->getFilenameWithoutExtension();
            $type = $this->resolveAgentTypeFromTemplate($filename);

            if (! $type) {
                $summary['skipped']++;
                continue;
            }

            $content = File::get($file->getPathname());
            $profileData = $this->buildAgentProfileDataFromTemplate($filename, $type, $content);

            $existing = null;

            try {
                $existing = AgentProfile::query()->where('slug', $profileData['slug'])->first();
            } catch (\Throwable) {
                // Database connection not available (e.g. dry-run preview). Treat as new record.
                $existing = null;
            }

            if ($dryRun) {
                $summary[$existing ? 'updated' : 'created']++;
                continue;
            }

            if ($existing) {
                $this->agentProfiles->update($existing, $profileData);
                $summary['updated']++;
            } else {
                $this->agentProfiles->create($profileData);
                $summary['created']++;
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $statusData
     * @return array<int, array{code:string,title:string,meta:array,tasks:array,folder:string}>
     */
    private function collectSprints(string $basePath, array $statusData, ?array $filters, array &$warnings): array
    {
        if (! File::isDirectory($basePath)) {
            throw new FileNotFoundException("Delegation path not found: {$basePath}");
        }

        $directories = array_filter(File::directories($basePath), static fn ($dir) => Str::startsWith(basename($dir), 'sprint-'));
        usort($directories, static fn ($a, $b) => strcmp($a, $b));

        $sprints = [];

        foreach ($directories as $dir) {
            $folderName = basename($dir);
            $number = (string) Str::after($folderName, 'sprint-');
            $code = $this->normaliseSprintCode($number);

            if ($filters && ! in_array($code, $filters, true)) {
                continue;
            }

            $status = $statusData[$code] ?? null;

            if (! $status) {
                $warnings[] = "No status entry found for {$code}.";
            }

            $tasks = $this->collectSprintTasks($dir, $status ? $status['tasks'] : []);

            $sprints[] = [
                'code' => $code,
                'title' => $status['title'] ?? Str::headline($folderName),
                'meta' => $status['meta'] ?? [],
                'notes' => $status['notes'] ?? [],
                'folder' => $folderName,
                'path' => $dir,
                'tasks' => $tasks,
            ];
        }

        return $sprints;
    }

    /**
     * @param  array<int, array<string, mixed>>  $statusTasks
     */
    private function collectSprintTasks(string $sprintDir, array $statusTasks): array
    {
        $entries = [];

        foreach (File::directories($sprintDir) as $taskDir) {
            $basename = basename($taskDir);

            if ($basename === 'assets') {
                continue;
            }

            $taskId = $this->extractTaskId($basename);
            $statusEntry = $statusTasks[$taskId] ?? [];

            $documents = [
                'agent' => File::exists("{$taskDir}/AGENT.md"),
                'plan' => File::exists("{$taskDir}/PLAN.md"),
                'context' => File::exists("{$taskDir}/CONTEXT.md"),
                'todo' => File::exists("{$taskDir}/TODO.md"),
                'summary' => File::exists("{$taskDir}/IMPLEMENTATION_SUMMARY.md"),
            ];

            $contents = [
                'agent' => $documents['agent'] ? File::get("{$taskDir}/AGENT.md") : null,
                'plan' => $documents['plan'] ? File::get("{$taskDir}/PLAN.md") : null,
                'context' => $documents['context'] ? File::get("{$taskDir}/CONTEXT.md") : null,
                'todo' => $documents['todo'] ? File::get("{$taskDir}/TODO.md") : null,
                'summary' => $documents['summary'] ? File::get("{$taskDir}/IMPLEMENTATION_SUMMARY.md") : null,
            ];

            $todoProgress = File::exists("{$taskDir}/TODO.md")
                ? $this->parseTodoProgress(File::get("{$taskDir}/TODO.md"))
                : ['completed' => 0, 'total' => 0];

            $estimateText = $statusEntry['estimate'] ?? null;

            $entries[] = [
                'code' => $taskId,
                'name' => $statusEntry['description'] ?? Str::headline($basename),
                'status' => $statusEntry['status'] ?? 'unknown',
                'agent' => $statusEntry['agent'] ?? null,
                'estimate' => $estimateText,
                'estimated_hours' => $estimateText ? $this->normaliseEstimateToHours($estimateText) : null,
                'path' => $taskDir,
                'relative_path' => Str::after($taskDir, base_path().'/'),
                'documents' => $documents,
                'contents' => $contents,
                'todo_progress' => $todoProgress,
                'metadata' => [
                    'dependencies' => $statusEntry['dependencies'] ?? null,
                    'raw' => $statusEntry,
                ],
            ];
        }

        usort($entries, static fn ($a, $b) => strcmp($a['code'], $b['code']));

        return $entries;
    }

    private function persistSprint(array $sprint): array
    {
        $model = Sprint::query()->firstOrNew(['code' => $sprint['code']]);
        $wasCreated = ! $model->exists;

        $meta = $model->meta ?? [];
        $model->meta = array_merge($meta, [
            'title' => $sprint['title'],
            'priority' => $sprint['meta']['priority'] ?? ($meta['priority'] ?? null),
            'estimate' => $sprint['meta']['estimated'] ?? ($meta['estimate'] ?? null),
            'impact' => $sprint['meta']['impact'] ?? ($meta['impact'] ?? null),
            'notes' => $sprint['notes'],
            'source_folder' => $sprint['folder'],
        ]);

        $model->save();

        return [$wasCreated, $model];
    }

    private function persistWorkItem(array $task, array $sprint, string $timestamp): array
    {
        $query = WorkItem::query()->where('metadata->source_path', $task['relative_path']);
        $model = $query->first();
        $created = false;

        if (! $model) {
            $model = new WorkItem();
            $model->type = 'task';
            $model->tags = ['delegation'];
            $created = true;
        }

        $model->status = $this->mapStatus($task['status']);
        $model->priority = $sprint['meta']['priority'] ?? $model->priority;
        $model->metadata = array_merge($model->metadata ?? [], [
            'task_code' => $task['code'],
            'task_name' => $task['name'],
            'description' => $task['name'],
            'sprint_code' => $sprint['code'],
            'estimate_text' => $task['estimate'],
            'agent_recommendation' => $task['agent'],
            'documents' => $task['documents'],
            'todo_progress' => $task['todo_progress'],
            'source_path' => $task['relative_path'],
        ]);
        $model->state = array_merge($model->state ?? [], [
            'sprint' => $sprint['code'],
            'task_code' => $task['code'],
        ]);
        $model->delegation_status = $this->mapDelegationStatus($task['status']);
        $model->delegation_context = array_merge($model->delegation_context ?? [], [
            'status_text' => $task['status'],
            'agent_recommendation' => $task['agent'],
            'estimate_text' => $task['estimate'],
            'sprint_code' => $sprint['code'],
        ]);
        $model->delegation_history = [[
            'action' => 'imported_from_delegation',
            'timestamp' => $timestamp,
            'path' => $task['relative_path'],
        ]];
        $model->estimated_hours = $task['estimated_hours'];

        $model->save();

        return [$created, $model];
    }

    private function persistSprintItem(Sprint $sprint, WorkItem $workItem, int $position): void
    {
        SprintItem::query()->updateOrCreate(
            ['sprint_id' => $sprint->id, 'work_item_id' => $workItem->id],
            ['position' => $position]
        );
    }

    private function normaliseSprintFilter(null|string|array $filter): ?array
    {
        if ($filter === null || $filter === '') {
            return null;
        }

        $values = is_array($filter) ? $filter : explode(',', (string) $filter);

        $normalised = array_filter(array_map(function ($value) {
            $value = trim((string) $value);

            if ($value === '') {
                return null;
            }

            if (preg_match('/^\d+$/', $value)) {
                return $this->normaliseSprintCode($value);
            }

            if (preg_match('/^(?:sprint-)?(\d+)$/i', $value, $matches)) {
                return $this->normaliseSprintCode($matches[1]);
            }

            if (preg_match('/^sprint-\d+$/i', $value)) {
                return $this->normaliseSprintCode(Str::after($value, '-'));
            }

            if (preg_match('/^SPRINT-\d+$/', strtoupper($value))) {
                return strtoupper($value);
            }

            return null;
        }, $values));

        return $normalised ? array_values(array_unique($normalised)) : null;
    }

    private function normaliseSprintCode(string $number): string
    {
        $number = ltrim($number, '0');

        if ($number === '') {
            $number = '0';
        }

        return 'SPRINT-'.str_pad($number, 2, '0', STR_PAD_LEFT);
    }

    private function parseTableHeaders(string $line): array
    {
        $segments = array_map(static fn ($value) => Str::of($value)->trim()->lower()->value(), explode('|', trim($line, '| ')));

        $headers = [];

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            $headers[$index] = $segment;
        }

        return $headers;
    }

    private function parseTableRow(string $line, array $headers): array
    {
        $segments = array_map(static fn ($value) => trim($value), explode('|', trim($line, '| ')));

        $row = [];

        foreach ($headers as $index => $header) {
            $value = $segments[$index] ?? '';
            $value = Str::of($value)->replace('**', '')->replace('`', '')->trim()->value();

            switch ($header) {
                case 'task id':
                    $row['task_id'] = $value;
                    break;
                case 'description':
                    $row['description'] = $value;
                    break;
                case 'status':
                    $row['status'] = Str::lower($value);
                    break;
                case 'estimated':
                    $row['estimate'] = $value;
                    break;
                case 'agent':
                    $row['agent'] = $value !== '' ? $value : null;
                    break;
                case 'dependencies':
                    $row['dependencies'] = $value !== '' ? $value : null;
                    break;
            }
        }

        return $row;
    }

    private function parseMetaLine(string $line): array
    {
        $meta = [];
        $segments = explode('|', $line);

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if (! Str::contains($segment, '**')) {
                continue;
            }

            $label = (string) Str::of($segment)->betweenFirst('**', '**')->trim();
            $value = (string) Str::of($segment)->after('**:')->trim();

            if ($label === '') {
                continue;
            }

            $key = Str::of($label)->snake()->value();
            $meta[$key] = $value;
        }

        return $meta;
    }

    private function resolveAgentTypeFromTemplate(string $filename): ?string
    {
        $slug = Str::kebab($filename);

        foreach (array_map(static fn ($case) => $case->value, \App\Enums\AgentType::cases()) as $value) {
            if ($value === $slug) {
                return $value;
            }
        }

        return null;
    }

    private function buildAgentProfileDataFromTemplate(string $filename, string $type, string $content): array
    {
        $heading = (string) Str::of($content)
            ->before("\n")
            ->after('# ')
            ->trim();

        $name = $heading !== '' ? Str::replace('Agent Template', 'Template', $heading) : Str::headline($filename).' Template';
        $slug = Str::slug($name);

        $description = (string) Str::of($content)
            ->after('# ')
            ->after('\n\n')
            ->before('##')
            ->stripTags()
            ->trim();

        if ($description === '') {
            $description = 'Agent profile imported from delegation template.';
        }

        $capabilities = [];

        foreach (preg_split('/\r?\n/', $content) as $line) {
            $line = trim($line);

            if (Str::startsWith($line, '- ')) {
                $capabilities[] = Str::after($line, '- ');
            }

            if (count($capabilities) >= 6) {
                break;
            }
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'description' => $description,
            'capabilities' => $capabilities ?: null,
            'constraints' => null,
            'tools' => null,
            'metadata' => [
                'source' => 'delegation-template',
                'template' => $filename,
            ],
            'status' => \App\Enums\AgentStatus::Active->value,
        ];
    }

    private function extractTaskId(string $directory): string
    {
        if (preg_match('/^[A-Z0-9]+-\d+(?:-\d+)?/', $directory, $matches)) {
            return $matches[0];
        }

        return Str::upper(Str::slug($directory));
    }

    private function parseTodoProgress(string $content): array
    {
        $completed = preg_match_all('/\[(x|X)\]/', $content);
        $total = preg_match_all('/\[(?: |x|X)\]/', $content);

        return [
            'completed' => $completed,
            'total' => $total,
        ];
    }

    private function normaliseEstimateToHours(string $estimate): ?float
    {
        if (! preg_match_all('/(\d+(?:\.\d+)?)/', $estimate, $matches)) {
            return null;
        }

        $numbers = array_map('floatval', $matches[1]);

        if ($numbers === []) {
            return null;
        }

        if (str_contains(Str::lower($estimate), 'min')) {
            // convert minutes to hours
            $numbers = array_map(static fn ($value) => $value / 60, $numbers);
        }

        if (count($numbers) === 1) {
            return $numbers[0];
        }

        return array_sum($numbers) / count($numbers);
    }

    private function mapStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'done', 'completed' => 'done',
            'in-progress', 'in_progress', 'wip' => 'in-progress',
            'review' => 'review',
            'blocked' => 'blocked',
            'todo', 'ready' => 'todo',
            default => 'backlog',
        };
    }

    private function mapDelegationStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'done', 'completed' => 'completed',
            'in-progress', 'in_progress', 'wip' => 'in_progress',
            'review' => 'in_progress',
            'blocked' => 'blocked',
            'assigned' => 'assigned',
            default => 'unassigned',
        };
    }
}
