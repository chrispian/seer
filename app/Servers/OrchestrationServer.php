<?php

namespace App\Servers;

use App\Tools\Orchestration\AgentDetailTool;
use App\Tools\Orchestration\AgentsListTool;
use App\Tools\Orchestration\AgentSaveTool;
use App\Tools\Orchestration\AgentStatusTool;
use App\Tools\Orchestration\SprintDetailTool;
use App\Tools\Orchestration\SprintSaveTool;
use App\Tools\Orchestration\SprintsListTool;
use App\Tools\Orchestration\SprintStatusTool;
use App\Tools\Orchestration\SprintTasksAttachTool;
use App\Tools\Orchestration\TaskAssignTool;
use App\Tools\Orchestration\TaskDetailTool;
use App\Tools\Orchestration\TasksListTool;
use App\Tools\Orchestration\TaskSaveTool;
use App\Tools\Orchestration\TaskStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;

class OrchestrationServer extends Server
{
    protected string $name = 'Fragments Engine Orchestration';
    protected string $version = '1.0.0';
    protected string $instructions = 'Fragments Engine orchestration system for managing sprints, tasks, and agents.';

    protected array $tools = [
        AgentsListTool::class,
        AgentDetailTool::class,
        AgentSaveTool::class,
        AgentStatusTool::class,
        SprintsListTool::class,
        TasksListTool::class,
        TaskSaveTool::class,
        TaskDetailTool::class,
        TaskAssignTool::class,
        TaskStatusTool::class,
        SprintDetailTool::class,
        SprintSaveTool::class,
        SprintStatusTool::class,
        SprintTasksAttachTool::class,
    ];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
    }

    protected function boot(): void
    {
        $this->tools = collect($this->tools)->filter(function ($toolClass) {
            $toolName = $this->getToolConfigKey($toolClass);
            return config('orchestration.enabled_tools.' . $toolName, true);
        })->values()->all();
    }

    private function getToolConfigKey(string $toolClass): string
    {
        $map = [
            AgentsListTool::class => 'orchestration_agents_list',
            AgentDetailTool::class => 'orchestration_agents_detail',
            AgentSaveTool::class => 'orchestration_agents_save',
            AgentStatusTool::class => 'orchestration_agents_status',
            SprintsListTool::class => 'orchestration_sprints_list',
            TasksListTool::class => 'orchestration_tasks_list',
            TaskSaveTool::class => 'orchestration_tasks_save',
            TaskDetailTool::class => 'orchestration_tasks_detail',
            TaskAssignTool::class => 'orchestration_tasks_assign',
            TaskStatusTool::class => 'orchestration_tasks_status',
            SprintDetailTool::class => 'orchestration_sprints_detail',
            SprintSaveTool::class => 'orchestration_sprints_save',
            SprintStatusTool::class => 'orchestration_sprints_status',
            SprintTasksAttachTool::class => 'orchestration_sprints_attach_tasks',
        ];

        return $map[$toolClass] ?? '';
    }
}
