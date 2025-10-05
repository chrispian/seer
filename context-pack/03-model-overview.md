# Model Overview

## Summary
- Models scanned: 46
- Relationships detected: 10
  - HASMANY: 3
  - BELONGSTO: 7

## Entity Relationship Diagram
```mermaid
erDiagram

    %% Legend:
    %% ||--|| : one-to-one
    %% o{--|| : many-to-one (belongs to)
    %% ||--o{ : one-to-many
    %% [pivot] : uses pivot table (belongsToMany)
    %% [polymorphic] : polymorphic relationship

    Category ||--o{ Fragment : fragments
    TelemetryCorrelationChain ||--o{ TelemetryEvent::class, 'correlation_id', 'root_correlation_id : events
    Bookmark o{--|| Vault : vault
    Bookmark o{--|| Project : project
    VaultRoutingRule o{--|| Vault::class, 'target_vault_id : targetVault
    VaultRoutingRule o{--|| Project::class, 'target_project_id : targetProject
    VaultRoutingRule o{--|| Vault::class, 'scope_vault_id : contextVault
    VaultRoutingRule o{--|| Project::class, 'scope_project_id : contextProject
    TelemetryEvent o{--|| TelemetryCorrelationChain::class, 'correlation_id', 'root_correlation_id : correlationChain
    TelemetryEvent ||--o{ TelemetryEvent::class, 'correlation_id', 'correlation_id : relatedEvents
```

## App\Models\Category

- File: `Category.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

| Relationship | Target | Method |
|-------------|--------|--------|
| HASMANY | Fragment | fragments |

## App\Models\Meeting

- File: `Meeting.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\AgentNote

- File: `AgentNote.php`
- Table: `agent_notes`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `links: array, tags: array, provenance: array, ttl_at: datetime`

## App\Models\TelemetryMetric

- File: `TelemetryMetric.php`
- Table: `not specified`
- Fillable: `metric_name`, `component`, `metric_type`, `value`, `labels`, `timestamp`, `aggregation_period`
- Guarded: not specified
- Soft Deletes: no
- Casts: `value: decimal:6, labels: array, timestamp: datetime`

## App\Models\Type

- File: `Type.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\SeerLog

- File: `SeerLog.php`
- Table: `fragments`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\TelemetryHealthCheck

- File: `TelemetryHealthCheck.php`
- Table: `not specified`
- Fillable: `component`, `check_name`, `is_healthy`, `error_message`, `response_time_ms`, `check_metadata`, `checked_at`
- Guarded: not specified
- Soft Deletes: no
- Casts: `is_healthy: boolean, response_time_ms: decimal:3, check_metadata: array, checked_at: datetime`

## App\Models\Link

- File: `Link.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\FragmentLink

- File: `FragmentLink.php`
- Table: `fragment_links`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `relation: RelationType`

## App\Models\Build

- File: `Build.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\WorkItemEvent

- File: `WorkItemEvent.php`
- Table: `work_item_events`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `meta: array`

## App\Models\FileText

- File: `FileText.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\Sprint

- File: `Sprint.php`
- Table: `sprints`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `meta: array`

## App\Models\File

- File: `File.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\ScheduleRun

- File: `ScheduleRun.php`
- Table: `not specified`
- Fillable: `schedule_id`, `planned_run_at`, `started_at`, `completed_at`, `status`, `output`, `error_message`, `duration_ms`, `job_id`, `dedupe_key`
- Guarded: not specified
- Soft Deletes: no
- Casts: `planned_run_at: datetime, started_at: datetime, completed_at: datetime, duration_ms: integer`

## App\Models\SprintItem

- File: `SprintItem.php`
- Table: `sprint_items`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\ChatSession

- File: `ChatSession.php`
- Table: `not specified`
- Fillable: `vault_id`, `project_id`, `title`, `short_code`, `custom_name`, `summary`, `messages`, `metadata`, `message_count`, `last_activity_at`, `is_active`, `is_pinned`, `sort_order`, `model_provider`, `model_name`
- Guarded: not specified
- Soft Deletes: yes
- Casts: `vault_id: integer, project_id: integer, messages: array, metadata: array, last_activity_at: datetime, is_active: boolean, is_pinned: boolean, sort_order: integer, model_provider: string, model_name: string`
- Accessors/Mutators:
  - Accessor: `displayTitle`
  - Accessor: `sidebarTitle`
  - Accessor: `lastMessagePreview`
  - Accessor: `displayName`
  - Accessor: `channelDisplay`
  - Accessor: `channelSidebarDisplay`

## App\Models\Vault

- File: `Vault.php`
- Table: `not specified`
- Fillable: `name`, `description`, `is_default`, `sort_order`, `metadata`
- Guarded: not specified
- Soft Deletes: no
- Casts: `is_default: boolean, sort_order: integer, metadata: array`

## App\Models\User

- File: `User.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Accessors/Mutators:
  - Accessor: `displayName`
  - Accessor: `avatarUrl`

## App\Models\Todo

- File: `Todo.php`
- Table: `todos`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `state: array`

## App\Models\FragmentTypeRegistry

- File: `FragmentTypeRegistry.php`
- Table: `fragment_type_registry`
- Fillable: `slug`, `version`, `source_path`, `schema_hash`, `hot_fields`, `capabilities`
- Guarded: not specified
- Soft Deletes: no
- Casts: `hot_fields: array, capabilities: array`

## App\Models\AgentDecision

- File: `AgentDecision.php`
- Table: `agent_decisions`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `alternatives: array, links: array, confidence: float`

## App\Models\ObjectType

- File: `ObjectType.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\Contact

- File: `Contact.php`
- Table: `not specified`
- Fillable: `fragment_id`, `full_name`, `emails`, `phones`, `organization`, `state`
- Guarded: not specified
- Soft Deletes: no
- Casts: `emails: array, phones: array, state: array`
- Accessors/Mutators:
  - Accessor: `primaryEmail`
  - Accessor: `displayName`

## App\Models\TelemetryCorrelationChain

- File: `TelemetryCorrelationChain.php`
- Table: `not specified`
- Fillable: `chain_id`, `root_correlation_id`, `depth`, `started_at`, `completed_at`, `total_events`, `chain_metadata`, `status`
- Guarded: not specified
- Soft Deletes: no
- Casts: `started_at: datetime, completed_at: datetime, chain_metadata: array`

| Relationship | Target | Method |
|-------------|--------|--------|
| HASMANY | TelemetryEvent::class, 'correlation_id', 'root_correlation_id | events |

## App\Models\Project

- File: `Project.php`
- Table: `not specified`
- Fillable: `vault_id`, `name`, `description`, `is_default`, `sort_order`, `metadata`
- Guarded: not specified
- Soft Deletes: no
- Casts: `vault_id: integer, is_default: boolean, sort_order: integer, metadata: array`

## App\Models\Fragment

- File: `Fragment.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: yes
- Casts: `project_id: integer, pinned: boolean, importance: integer, confidence: integer, tags: array, relationships: array, metadata: array, parsed_entities: array, selection_stats: array, state: array, state_json: array, deleted_at: datetime, hash_bucket: integer, model_provider: string, model_name: string, inbox_at: datetime, reviewed_at: datetime`
- Accessors/Mutators:
  - Accessor: `title`
  - Accessor: `preview`
  - Accessor: `body`

## App\Models\Artifact

- File: `Artifact.php`
- Table: `artifacts`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `metadata: array`

## App\Models\Bookmark

- File: `Bookmark.php`
- Table: `not specified`
- Fillable: `name`, `fragment_ids`, `last_viewed_at`, `vault_id`, `project_id`
- Guarded: not specified
- Soft Deletes: no
- Casts: `fragment_ids: array, last_viewed_at: datetime, vault_id: integer, project_id: integer`
- Accessors/Mutators:
  - Accessor: `firstFragment`

| Relationship | Target | Method |
|-------------|--------|--------|
| BELONGSTO | Vault | vault |
| BELONGSTO | Project | project |

## App\Models\ArticleFragment

- File: `ArticleFragment.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\Schedule

- File: `Schedule.php`
- Table: `not specified`
- Fillable: `name`, `command_slug`, `payload`, `status`, `recurrence_type`, `recurrence_value`, `timezone`, `next_run_at`, `last_run_at`, `locked_at`, `lock_owner`, `last_tick_at`, `run_count`, `max_runs`
- Guarded: not specified
- Soft Deletes: no
- Casts: `payload: array, next_run_at: datetime, last_run_at: datetime, locked_at: datetime, last_tick_at: datetime, run_count: integer, max_runs: integer`

## App\Models\FragmentTag

- File: `FragmentTag.php`
- Table: `fragment_tags`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\VaultRoutingRule

- File: `VaultRoutingRule.php`
- Table: `not specified`
- Fillable: `name`, `match_type`, `match_value`, `conditions`, `target_vault_id`, `target_project_id`, `scope_vault_id`, `scope_project_id`, `priority`, `is_active`, `notes`
- Guarded: not specified
- Soft Deletes: no
- Casts: `conditions: array, is_active: boolean`

| Relationship | Target | Method |
|-------------|--------|--------|
| BELONGSTO | Vault::class, 'target_vault_id | targetVault |
| BELONGSTO | Project::class, 'target_project_id | targetProject |
| BELONGSTO | Vault::class, 'scope_vault_id | contextVault |
| BELONGSTO | Project::class, 'scope_project_id | contextProject |

## App\Models\Thumbnail

- File: `Thumbnail.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\WorkItem

- File: `WorkItem.php`
- Table: `work_items`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `tags: array, state: array, metadata: array`

## App\Models\AICredential

- File: `AICredential.php`
- Table: `not specified`
- Fillable: `provider`, `credential_type`, `encrypted_credentials`, `metadata`, `expires_at`, `is_active`
- Guarded: not specified
- Soft Deletes: no

## App\Models\PromptEntry

- File: `PromptEntry.php`
- Table: `prompt_registry`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `variables: array, tags: array`

## App\Models\CommandRegistry

- File: `CommandRegistry.php`
- Table: `command_registry`
- Fillable: `slug`, `version`, `source_path`, `steps_hash`, `capabilities`, `requires_secrets`, `reserved`
- Guarded: not specified
- Soft Deletes: no
- Casts: `capabilities: array, requires_secrets: array, reserved: boolean`

## App\Models\TelemetryEvent

- File: `TelemetryEvent.php`
- Table: `not specified`
- Fillable: `correlation_id`, `event_type`, `event_name`, `timestamp`, `component`, `operation`, `metadata`, `context`, `performance`, `message`, `level`
- Guarded: not specified
- Soft Deletes: no
- Casts: `timestamp: datetime, metadata: array, context: array, performance: array`

| Relationship | Target | Method |
|-------------|--------|--------|
| BELONGSTO | TelemetryCorrelationChain::class, 'correlation_id', 'root_correlation_id | correlationChain |
| HASMANY | TelemetryEvent::class, 'correlation_id', 'correlation_id | relatedEvents |

## App\Models\TelemetryPerformanceSnapshot

- File: `TelemetryPerformanceSnapshot.php`
- Table: `not specified`
- Fillable: `component`, `operation`, `duration_ms`, `memory_usage_bytes`, `cpu_usage_percent`, `resource_metrics`, `performance_class`, `recorded_at`
- Guarded: not specified
- Soft Deletes: no
- Casts: `duration_ms: decimal:3, memory_usage_bytes: integer, cpu_usage_percent: integer, resource_metrics: array, recorded_at: datetime`

## App\Models\Source

- File: `Source.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\CalendarEvent

- File: `CalendarEvent.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no

## App\Models\Article

- File: `Article.php`
- Table: `not specified`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `status: ArticleStatus, meta: array`

## App\Models\AgentVector

- File: `AgentVector.php`
- Table: `agent_vectors`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `embedding: array, meta: array`

## App\Models\SavedQuery

- File: `SavedQuery.php`
- Table: `saved_queries`
- Fillable: not specified
- Guarded: not specified
- Soft Deletes: no
- Casts: `filters: array, boosts: array, order_by: array`

## App\Models\RecallDecision

- File: `RecallDecision.php`
- Table: `not specified`
- Fillable: `user_id`, `query`, `parsed_query`, `total_results`, `selected_fragment_id`, `selected_index`, `action`, `context`, `decided_at`
- Guarded: not specified
- Soft Deletes: no

