<?php

namespace App\Console\Commands\Orchestration;

use App\Commands\Orchestration\Task\SaveCommand;
use Illuminate\Console\Command;

class OrchestrationTaskSaveCommand extends Command
{
    protected $signature = 'orchestration:task:save
        {task_code : Task code (e.g., T-ART-01)}
        {--task-name= : Task name}
        {--description= : Task description}
        {--sprint-code= : Sprint code}
        {--status= : Task status}
        {--delegation-status= : Delegation status}
        {--priority= : Priority}
        {--estimate-text= : Estimate text}
        {--estimated-hours= : Estimated hours (numeric)}
        {--type= : Task type}
        {--acceptance= : Acceptance criteria}
        {--agent-content= : Agent instructions}
        {--dependency=* : Task dependencies (repeatable)}
        {--tag=* : Tags (repeatable)}
        {--no-upsert : Fail if task already exists}
        {--json : Output JSON}';

    protected $description = 'Create or update a task';

    public function handle(): int
    {
        $dependencies = $this->option('dependency');
        $tags = $this->option('tag');

        $command = new SaveCommand([
            'task_code' => $this->argument('task_code'),
            'task_name' => $this->option('task-name'),
            'description' => $this->option('description'),
            'sprint_code' => $this->option('sprint-code'),
            'status' => $this->option('status'),
            'delegation_status' => $this->option('delegation-status'),
            'priority' => $this->option('priority'),
            'estimate_text' => $this->option('estimate-text'),
            'estimated_hours' => $this->option('estimated-hours') ? (float) $this->option('estimated-hours') : null,
            'type' => $this->option('type'),
            'acceptance' => $this->option('acceptance'),
            'agent_content' => $this->option('agent-content'),
            'dependencies' => $dependencies ?: null,
            'tags' => $tags ?: null,
            'upsert' => ! $this->option('no-upsert'),
        ]);

        $command->setContext('cli');
        $result = $command->handle();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $data = $result['data'];
        $this->info(sprintf('Task %s saved', $data['task_code'] ?? $this->argument('task_code')));

        $this->table(['Task', 'Name', 'Sprint', 'Status', 'Delegation'], [[
            $data['task_code'] ?? '—',
            $data['task_name'] ?? '—',
            $data['sprint_code'] ?? '—',
            $data['status'] ?? '—',
            $data['delegation_status'] ?? '—',
        ]]);

        return self::SUCCESS;
    }
}
