<?php

namespace Database\Seeders;

use App\Models\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CommandsSeeder extends Seeder
{
    /**
     * Seed the commands table with existing commands.
     * 
     * IMPORTANT: This seeds the UNIFIED command system (slash commands, MCP tools).
     * 
     * DO NOT add artisan console commands here (e.g., orchestration:sprints).
     * Console commands are registered separately in app/Console/Commands/ and are
     * for CLI-only use, not part of the web UI or MCP system.
     * 
     * The unified system uses slash commands (/sprints) that work across:
     * - Web UI (chat interface)
     * - MCP (AI agents via Model Context Protocol)
     * - Direct PHP invocation
     */
    public function run(): void
    {
        $commands = [
            // Sprint commands
            [
                'command' => '/sprints',
                'name' => 'List Sprints',
                'description' => 'Display all sprints with task counts and status',
                'category' => 'Orchestration',
                'type_slug' => 'sprint',
                'handler_class' => 'App\\Commands\\Orchestration\\Sprint\\ListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => true,
                'ui_modal_container' => 'SprintListModal',
                'ui_layout_mode' => 'table',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'updated_at', 'direction' => 'desc'],
                'pagination_default' => 25,
                'is_active' => true,
            ],
            [
                'command' => '/sprint-detail',
                'name' => 'Sprint Detail',
                'description' => 'View detailed information about a specific sprint',
                'category' => 'Orchestration',
                'type_slug' => 'sprint',
                'handler_class' => 'App\\Commands\\Orchestration\\Sprint\\DetailCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => true,
                'ui_modal_container' => 'Dialog',
                'ui_layout_mode' => null,
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => null,
                'pagination_default' => 25,
                'is_active' => true,
            ],
            
            // Task commands
            [
                'command' => '/tasks',
                'name' => 'List Tasks',
                'description' => 'Display all tasks with status and assignment info',
                'category' => 'Orchestration',
                'type_slug' => 'task',
                'handler_class' => 'App\\Commands\\Orchestration\\Task\\ListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => true,
                'ui_modal_container' => 'DataManagementModal',
                'ui_layout_mode' => 'table',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'updated_at', 'direction' => 'desc'],
                'pagination_default' => 25,
                'is_active' => true,
            ],
            [
                'command' => '/task-detail',
                'name' => 'Task Detail',
                'description' => 'View detailed information about a specific task',
                'category' => 'Orchestration',
                'type_slug' => 'task',
                'handler_class' => 'App\\Commands\\Orchestration\\Task\\DetailCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => true,
                'ui_modal_container' => 'Dialog',
                'ui_layout_mode' => null,
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => null,
                'pagination_default' => 25,
                'is_active' => true,
            ],
            
            // Backlog commands
            [
                'command' => '/backlog',
                'name' => 'Backlog Items',
                'description' => 'Display all backlog items for future planning',
                'category' => 'Orchestration',
                'type_slug' => 'task',
                'handler_class' => 'App\\Commands\\Orchestration\\Backlog\\ListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => true,
                'ui_modal_container' => 'BacklogListModal',
                'ui_layout_mode' => 'table',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'priority', 'direction' => 'desc'],
                'pagination_default' => 50,
                'navigation_config' => [
                    'data_prop' => 'tasks',
                    'item_key' => 'task_code',
                    'detail_command' => '/task-detail',
                ],
                'is_active' => true,
            ],
            
            // Agent commands
            [
                'command' => '/agents',
                'name' => 'List Agents',
                'description' => 'Display all agent profiles',
                'category' => 'Orchestration',
                'type_slug' => 'agent',
                'handler_class' => 'App\\Commands\\Orchestration\\Agent\\ListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => true,
                'ui_modal_container' => 'AgentProfileGridModal',
                'ui_layout_mode' => 'grid',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'name', 'direction' => 'asc'],
                'pagination_default' => 50,
                'navigation_config' => [
                    'data_prop' => 'agents',
                    'item_key' => 'slug',
                    'detail_command' => null,
                ],
                'is_active' => true,
            ],
            
            // Project/Vault/Bookmark commands
            [
                'command' => '/projects',
                'name' => 'List Projects',
                'description' => 'Display all projects',
                'category' => 'Navigation',
                'type_slug' => 'project',
                'handler_class' => 'App\\Commands\\ProjectListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => false,
                'ui_modal_container' => 'DataManagementModal',
                'ui_layout_mode' => 'table',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'name', 'direction' => 'asc'],
                'pagination_default' => 25,
                'is_active' => true,
            ],
            [
                'command' => '/vaults',
                'name' => 'List Vaults',
                'description' => 'Display all vaults',
                'category' => 'Navigation',
                'type_slug' => 'vault',
                'handler_class' => 'App\\Commands\\VaultListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => false,
                'ui_modal_container' => 'DataManagementModal',
                'ui_layout_mode' => 'table',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'name', 'direction' => 'asc'],
                'pagination_default' => 25,
                'is_active' => true,
            ],
            [
                'command' => '/bookmarks',
                'name' => 'List Bookmarks',
                'description' => 'Display all bookmarks',
                'category' => 'Navigation',
                'type_slug' => 'bookmark',
                'handler_class' => 'App\\Commands\\BookmarkListCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => false,
                'ui_modal_container' => 'DataManagementModal',
                'ui_layout_mode' => 'table',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => ['field' => 'created_at', 'direction' => 'desc'],
                'pagination_default' => 25,
                'is_active' => true,
            ],
            [
                'command' => '/todos',
                'name' => 'Manage Todos',
                'description' => 'View and manage todo items',
                'category' => 'Productivity',
                'type_slug' => null,
                'handler_class' => 'App\\Commands\\TodoCommand',
                'available_in_slash' => true,
                'available_in_cli' => false,
                'available_in_mcp' => false,
                'ui_modal_container' => 'DataManagementModal',
                'ui_layout_mode' => 'list',
                'ui_card_component' => null,
                'ui_detail_component' => null,
                'filters' => null,
                'default_sort' => null,
                'pagination_default' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($commands as $commandData) {
            Command::updateOrCreate(
                ['command' => $commandData['command']],
                $commandData
            );
        }

        Cache::forget('command_registry');
        \App\Services\CommandRegistry::clearCache();

        $this->command->info('✅ Seeded ' . count($commands) . ' commands and cleared cache');
    }
}
