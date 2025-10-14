<?php

return [
    'enabled_tools' => [
        'orchestration_agents_list' => true,
        'orchestration_agents_detail' => true,
        'orchestration_agents_save' => true,
        'orchestration_agents_status' => true,
        'orchestration_tasks_list' => true,
        'orchestration_tasks_save' => true,
        'orchestration_sprints_list' => true,
        'orchestration_tasks_detail' => true,
        'orchestration_tasks_assign' => true,
        'orchestration_tasks_status' => true,
        'orchestration_sprints_detail' => true,
        'orchestration_sprints_save' => true,
        'orchestration_sprints_status' => true,
        'orchestration_sprints_attach_tasks' => true,
        'orchestration_messages_check' => true,
        'orchestration_message_ack' => true,
        'orchestration_artifacts_pull' => true,
        'orchestration_handoff' => true,
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
            'orchestration_sprints_list',
            'orchestration_tasks_detail',
            'orchestration_tasks_assign',
            'orchestration_tasks_status',
            'orchestration_sprints_detail',
            'orchestration_sprints_save',
            'orchestration_sprints_status',
            'orchestration_sprints_attach_tasks',
            'orchestration_messages_check',
            'orchestration_message_ack',
            'orchestration_artifacts_pull',
            'orchestration_handoff',
        ],
    ],

    'models' => [
        'agent_model' => \App\Models\AgentProfile::class,
        'sprint_model' => \App\Models\Sprint::class,
        'work_item_model' => \App\Models\WorkItem::class,
    ],

    'services' => [
        'task_service' => \App\Services\TaskOrchestrationService::class,
        'sprint_service' => \App\Services\SprintOrchestrationService::class,
        'agent_service' => \App\Services\AgentOrchestrationService::class,
    ],

    'artifacts' => [
        'disk' => env('FE_ARTIFACTS_DISK', 'local'),
        'root' => env('FE_ARTIFACTS_ROOT', 'orchestration/artifacts'),
    ],

    'messaging' => [
        'retention_days' => (int) env('FE_MSG_RETENTION_DAYS', 365),
    ],

    'secret_redaction' => [
        'enabled' => env('FE_REDACTION_ENABLED', true),
        'patterns' => [
            'AWS_ACCESS_KEY_ID=\S+',
            'AWS_SECRET_ACCESS_KEY=\S+',
            'APP_KEY=\S+',
            'Bearer \S+',
            'OPENAI_API_KEY=\S+',
            'ANTHROPIC_API_KEY=\S+',
        ],
        'custom_patterns' => env('FE_REDACT_PATTERNS')
            ? explode(',', env('FE_REDACT_PATTERNS'))
            : [],
    ],

    'search' => [
        'driver' => env('ORCHESTRATION_SEARCH_DRIVER', 'fulltext'),
        'cache_ttl' => (int) env('ORCHESTRATION_SEARCH_CACHE_TTL', 3600),
        'index_fields' => ['event_type', 'entity_type', 'payload'],
    ],

    'workflow' => [
        'allow_phase_skip' => false,
        'allow_user_override' => true,
        'require_artifact_validation' => true,
        'sync_files_on_phase_complete' => false,
        'sync_files_on_close' => true,
    ],

    'git' => [
        'enabled' => env('ORCHESTRATION_GIT_ENABLED', true),
        'auto_commit' => env('ORCHESTRATION_GIT_AUTO_COMMIT', false),
        'commit_message_template' => 'feat({sprint_code}): {task_title} [TSK-{task_code}]',
        'track_commits' => true,
    ],
];
