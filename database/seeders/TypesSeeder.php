<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypesSeeder extends Seeder
{
    /**
     * Seed the types_registry table with initial types.
     * 
     * Defines both model-backed types (Sprint, Task, Agent) and 
     * placeholder fragment-backed types for future use.
     */
    public function run(): void
    {
        $types = [
            // Model-backed types (have their own tables)
            [
                'slug' => 'sprint',
                'display_name' => 'Sprint',
                'plural_name' => 'Sprints',
                'description' => 'Sprint for organizing tasks in time-boxed iterations',
                'icon' => 'calendar',
                'color' => '#3b82f6',
                'storage_type' => 'model',
                'model_class' => 'App\\Models\\OrchestrationSprint',
                'schema' => null,
                'default_card_component' => 'SprintCard',
                'default_detail_component' => 'SprintDetailModal',
                'capabilities' => ['searchable', 'filterable', 'sortable'],
                'hot_fields' => ['code', 'title', 'status'],
                'is_enabled' => true,
                'is_system' => true,
            ],
            [
                'slug' => 'task',
                'display_name' => 'Task',
                'plural_name' => 'Tasks',
                'description' => 'Work item or task in the orchestration system',
                'icon' => 'check-square',
                'color' => '#10b981',
                'storage_type' => 'model',
                'model_class' => 'App\\Models\\OrchestrationTask',
                'schema' => null,
                'default_card_component' => 'TaskCard',
                'default_detail_component' => 'TaskDetailModal',
                'capabilities' => ['searchable', 'filterable', 'sortable', 'assignable'],
                'hot_fields' => ['task_code', 'task_name', 'status', 'delegation_status'],
                'is_enabled' => true,
                'is_system' => true,
            ],
            [
                'slug' => 'agent',
                'display_name' => 'Agent',
                'plural_name' => 'Agents',
                'description' => 'AI agent profile with capabilities and tools',
                'icon' => 'bot',
                'color' => '#8b5cf6',
                'storage_type' => 'model',
                'model_class' => 'App\\Models\\OrchestrationAgentProfile',
                'schema' => null,
                'default_card_component' => 'AgentProfileMiniCard',
                'default_detail_component' => 'AgentProfileGridModal',
                'capabilities' => ['searchable', 'filterable'],
                'hot_fields' => ['slug', 'name', 'type', 'status'],
                'is_enabled' => true,
                'is_system' => true,
            ],
            [
                'slug' => 'project',
                'display_name' => 'Project',
                'plural_name' => 'Projects',
                'description' => 'Project for organizing fragments and work',
                'icon' => 'folder',
                'color' => '#f59e0b',
                'storage_type' => 'model',
                'model_class' => 'App\\Models\\Project',
                'schema' => null,
                'default_card_component' => 'ProjectCard',
                'default_detail_component' => 'ProjectDetailModal',
                'capabilities' => ['searchable'],
                'hot_fields' => ['name', 'description'],
                'is_enabled' => true,
                'is_system' => false,
            ],
            [
                'slug' => 'vault',
                'display_name' => 'Vault',
                'plural_name' => 'Vaults',
                'description' => 'Secure storage container for projects',
                'icon' => 'database',
                'color' => '#6366f1',
                'storage_type' => 'model',
                'model_class' => 'App\\Models\\Vault',
                'schema' => null,
                'default_card_component' => 'VaultCard',
                'default_detail_component' => 'VaultDetailModal',
                'capabilities' => ['searchable'],
                'hot_fields' => ['name'],
                'is_enabled' => true,
                'is_system' => false,
            ],
            
            // Fragment-backed types (stored as fragments with schema)
            [
                'slug' => 'note',
                'display_name' => 'Note',
                'plural_name' => 'Notes',
                'description' => 'General purpose note or memo',
                'icon' => 'sticky-note',
                'color' => '#eab308',
                'storage_type' => 'fragment',
                'model_class' => null,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'content' => ['type' => 'string'],
                        'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'required' => ['content'],
                ],
                'default_card_component' => 'NoteCard',
                'default_detail_component' => 'UnifiedDetailModal',
                'capabilities' => ['searchable', 'taggable', 'ai_processable'],
                'hot_fields' => ['title', 'content'],
                'is_enabled' => true,
                'is_system' => false,
            ],
            [
                'slug' => 'bookmark',
                'display_name' => 'Bookmark',
                'plural_name' => 'Bookmarks',
                'description' => 'Saved reference or bookmark',
                'icon' => 'bookmark',
                'color' => '#f97316',
                'storage_type' => 'fragment',
                'model_class' => null,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'url' => ['type' => 'string', 'format' => 'uri'],
                        'description' => ['type' => 'string'],
                    ],
                    'required' => ['name'],
                ],
                'default_card_component' => 'BookmarkCard',
                'default_detail_component' => 'UnifiedDetailModal',
                'capabilities' => ['searchable'],
                'hot_fields' => ['name', 'url'],
                'is_enabled' => true,
                'is_system' => false,
            ],
            [
                'slug' => 'link',
                'display_name' => 'Link',
                'plural_name' => 'Links',
                'description' => 'Bookmarked URL or web resource',
                'icon' => 'link',
                'color' => '#06b6d4',
                'storage_type' => 'fragment',
                'model_class' => null,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => ['type' => 'string', 'format' => 'uri'],
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                    ],
                ],
                'default_card_component' => 'LinkCard',
                'default_detail_component' => 'UnifiedDetailModal',
                'capabilities' => ['searchable'],
                'hot_fields' => ['url', 'title'],
                'is_enabled' => true,
                'is_system' => false,
            ],
            [
                'slug' => 'todo',
                'display_name' => 'Todo',
                'plural_name' => 'Todos',
                'description' => 'Task or todo item',
                'icon' => 'check-square',
                'color' => '#10b981',
                'storage_type' => 'fragment',
                'model_class' => null,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'state' => ['type' => 'string'],
                        'tags' => ['type' => 'array'],
                    ],
                ],
                'default_card_component' => 'TodoCard',
                'default_detail_component' => 'TodoDetailModal',
                'capabilities' => ['searchable'],
                'hot_fields' => ['title', 'state', 'tags'],
                'is_enabled' => true,
                'is_system' => false,
            ],
        ];

        foreach ($types as $typeData) {
            Type::updateOrCreate(
                ['slug' => $typeData['slug']],
                $typeData
            );
        }

        \App\Services\CommandRegistry::clearCache();

        $this->command->info('✅ Seeded ' . count($types) . ' types and cleared CommandRegistry cache');
    }
}
