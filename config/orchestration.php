<?php

return [
    'enabled_tools' => [
        'orchestration_agents_list'  => true,
        'orchestration_agents_detail' => true,
        'orchestration_agents_save' => true,
        'orchestration_agents_status' => true,
        'orchestration_tasks_list'   => true,
        'orchestration_tasks_save'   => true,
        'orchestration_sprints_list' => true,
        'orchestration_tasks_detail' => true,
        'orchestration_tasks_assign' => true,
        'orchestration_tasks_status' => true,
        'orchestration_sprints_detail' => true,
        'orchestration_sprints_save' => true,
        'orchestration_sprints_status' => true,
        'orchestration_sprints_attach_tasks' => true,
    ],

    'priority_tools' => [
        'orchestration_agents_list',
        'orchestration_agents_save',
        'orchestration_tasks_list',
        'orchestration_tasks_save',
        'orchestration_tasks_detail',
        'orchestration_sprints_save',
    ],

    'categories' => [
        'Orchestration' => [
            'orchestration_agents_list',
            'orchestration_agents_detail',
            'orchestration_agents_save',
            'orchestration_agents_status',
            'orchestration_tasks_list',
            'orchestration_tasks_save',
            'orchestration_sprints_list',
            'orchestration_tasks_detail',
            'orchestration_tasks_assign',
            'orchestration_tasks_status',
            'orchestration_sprints_detail',
            'orchestration_sprints_save',
            'orchestration_sprints_status',
            'orchestration_sprints_attach_tasks',
        ],
    ],

    'models' => [
        'agent' => \App\Models\AgentProfile::class,
        'sprint' => \App\Models\Sprint::class,
        'work_item' => \App\Models\WorkItem::class,
    ],

    'services' => [
        'task' => \App\Services\TaskOrchestrationService::class,
        'sprint' => \App\Services\SprintOrchestrationService::class,
        'agent' => \App\Services\AgentOrchestrationService::class,
    ],
];
