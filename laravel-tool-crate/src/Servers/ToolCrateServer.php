<?php

namespace HollisLabs\ToolCrate\Servers;

use Laravel\Mcp\Server;
use HollisLabs\ToolCrate\Tools\JqQueryTool;
use HollisLabs\ToolCrate\Tools\TextSearchTool;
use HollisLabs\ToolCrate\Tools\FileReadTool;
use HollisLabs\ToolCrate\Tools\TextReplaceTool;
use HollisLabs\ToolCrate\Tools\HelpIndexTool;
use HollisLabs\ToolCrate\Tools\HelpToolDetail;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentsListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentDetailTool;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentSaveTool;
use HollisLabs\ToolCrate\Tools\Orchestration\AgentStatusTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintsListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TaskAssignTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TaskDetailTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TasksListTool;
use HollisLabs\ToolCrate\Tools\Orchestration\TaskStatusTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintDetailTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintSaveTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintStatusTool;
use HollisLabs\ToolCrate\Tools\Orchestration\SprintTasksAttachTool;

class ToolCrateServer extends Server
{
    protected string $name = 'ToolCrate';
    protected string $version = '0.2.0';
    protected string $instructions = 'Local dev toolbox. Use help.index to discover JSON, search, file, and orchestration helpers.';

    protected array $tools = [];

    public function __construct()
    {
        $map = [
            'json.query'                    => JqQueryTool::class,
            'text.search'                   => TextSearchTool::class,
            'file.read'                     => FileReadTool::class,
            'text.replace'                  => TextReplaceTool::class,
            'help.index'                    => HelpIndexTool::class,
            'help.tool'                     => HelpToolDetail::class,
            'orchestration.agents.list'     => AgentsListTool::class,
            'orchestration.agents.detail'   => AgentDetailTool::class,
            'orchestration.agents.save'     => AgentSaveTool::class,
            'orchestration.agents.status'   => AgentStatusTool::class,
            'orchestration.sprints.list'    => SprintsListTool::class,
            'orchestration.tasks.list'      => TasksListTool::class,
            'orchestration.tasks.detail'    => TaskDetailTool::class,
            'orchestration.tasks.assign'    => TaskAssignTool::class,
            'orchestration.tasks.status'    => TaskStatusTool::class,
            'orchestration.sprints.detail'  => SprintDetailTool::class,
            'orchestration.sprints.save'    => SprintSaveTool::class,
            'orchestration.sprints.status'  => SprintStatusTool::class,
            'orchestration.sprints.attach_tasks' => SprintTasksAttachTool::class,
        ];

        foreach ($map as $name => $cls) {
            if (config('tool-crate.enabled_tools.' . $name, false)) {
                $this->tools[] = $cls;
            }
        }
    }
}
