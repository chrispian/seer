<?php

namespace Database\Seeders;

use App\Models\FragmentTypeRegistry;
use Illuminate\Database\Seeder;

class SystemTypesSeeder extends Seeder
{
    /**
     * Seed system fragment types
     *
     * System types cannot be disabled or deleted.
     * Hidden types don't appear in admin UI.
     */
    public function run(): void
    {
        $systemTypes = [
            // Internal chat types (hidden from admin)
            [
                'slug' => 'user',
                'display_name' => 'User Message',
                'plural_name' => 'User Messages',
                'description' => 'Messages from users in chat conversations',
                'icon' => 'user',
                'color' => '#3B82F6',
                'is_system' => true,
                'is_enabled' => true,
                'hide_from_admin' => true,
                'version' => '1.0.0',
                'source_path' => 'system',
                'schema_hash' => hash('sha256', 'user'),
                'capabilities' => ['chat', 'conversational'],
            ],
            [
                'slug' => 'assistant',
                'display_name' => 'Assistant Response',
                'plural_name' => 'Assistant Responses',
                'description' => 'AI assistant responses in conversations',
                'icon' => 'bot',
                'color' => '#8B5CF6',
                'is_system' => true,
                'is_enabled' => true,
                'hide_from_admin' => true,
                'version' => '1.0.0',
                'source_path' => 'system',
                'schema_hash' => hash('sha256', 'assistant'),
                'capabilities' => ['chat', 'conversational', 'ai_generated'],
            ],
            [
                'slug' => 'system',
                'display_name' => 'System Message',
                'plural_name' => 'System Messages',
                'description' => 'System-generated status and notification messages',
                'icon' => 'cpu',
                'color' => '#6B7280',
                'is_system' => true,
                'is_enabled' => true,
                'hide_from_admin' => true,
                'version' => '1.0.0',
                'source_path' => 'system',
                'schema_hash' => hash('sha256', 'system'),
                'capabilities' => ['system_generated'],
            ],

            // User-facing types (visible in admin)
            [
                'slug' => 'bookmark',
                'display_name' => 'Bookmark',
                'plural_name' => 'Bookmarks',
                'description' => 'Saved bookmarks and important links',
                'icon' => 'bookmark',
                'color' => '#F59E0B',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,
                'version' => '1.0.0',
                'source_path' => 'user',
                'schema_hash' => hash('sha256', 'bookmark'),
                'capabilities' => ['user_created', 'taggable'],
                'behaviors' => ['linkable', 'categorizable'],
                'list_columns' => [
                    ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'width' => 'flex-1'],
                    ['key' => 'category', 'label' => 'Category', 'sortable' => true, 'width' => 'w-24'],
                    ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'width' => 'w-32'],
                ],
                'default_sort' => ['created_at', 'desc'],
                'pagination_default' => 50,
            ],
            [
                'slug' => 'todo',
                'display_name' => 'Todo',
                'plural_name' => 'Todos',
                'description' => 'Tasks and todo items with completion tracking',
                'icon' => 'check-square',
                'color' => '#10B981',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,
                'version' => '1.0.0',
                'source_path' => 'user',
                'schema_hash' => hash('sha256', 'todo'),
                'capabilities' => ['user_created', 'state_tracking'],
                'behaviors' => ['completable', 'prioritizable'],
                'config_class' => 'App\\Types\\Todo\\TodoTypeConfig',  // Has complex checkbox behavior
                'pagination_default' => 50,
            ],
            [
                'slug' => 'note',
                'display_name' => 'Note',
                'plural_name' => 'Notes',
                'description' => 'General notes, memos, and quick captures',
                'icon' => 'sticky-note',
                'color' => '#EAB308',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,
                'version' => '1.0.0',
                'source_path' => 'user',
                'schema_hash' => hash('sha256', 'note'),
                'capabilities' => ['user_created', 'taggable'],
                'pagination_default' => 50,
            ],
            [
                'slug' => 'log',
                'display_name' => 'Log Entry',
                'plural_name' => 'Log Entries',
                'description' => 'Journal entries and daily logs',
                'icon' => 'calendar',
                'color' => '#EC4899',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,
                'version' => '1.0.0',
                'source_path' => 'user',
                'schema_hash' => hash('sha256', 'log'),
                'capabilities' => ['user_created', 'time_series'],
                'pagination_default' => 50,
            ],
        ];

        foreach ($systemTypes as $type) {
            FragmentTypeRegistry::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        $this->command->info('✅ Seeded '.count($systemTypes).' system types');
    }
}
