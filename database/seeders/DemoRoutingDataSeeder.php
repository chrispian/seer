<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Type;
use App\Models\Vault;
use App\Models\VaultRoutingRule;
use Illuminate\Database\Seeder;

class DemoRoutingDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in development or if explicitly requested
        if (! app()->environment(['local', 'development']) && ! config('app.seed_demo_data', false)) {
            return;
        }

        // Create demo vaults
        $workVault = Vault::firstOrCreate(
            ['name' => 'work'],
            [
                'description' => 'Work-related fragments and projects',
                'is_default' => false,
                'sort_order' => 1,
            ]
        );

        $personalVault = Vault::firstOrCreate(
            ['name' => 'personal'],
            [
                'description' => 'Personal thoughts, ideas, and tasks',
                'is_default' => false,
                'sort_order' => 2,
            ]
        );

        $clientVault = Vault::firstOrCreate(
            ['name' => 'clients'],
            [
                'description' => 'Client communications and project materials',
                'is_default' => false,
                'sort_order' => 3,
            ]
        );

        // Create demo projects
        $workProject = Project::firstOrCreate(
            ['vault_id' => $workVault->id, 'name' => 'Engineering'],
            [
                'description' => 'Software engineering tasks and documentation',
                'is_default' => true,
                'sort_order' => 1,
            ]
        );

        $personalProject = Project::firstOrCreate(
            ['vault_id' => $personalVault->id, 'name' => 'Life Management'],
            [
                'description' => 'Personal productivity and life organization',
                'is_default' => true,
                'sort_order' => 1,
            ]
        );

        $clientProject = Project::firstOrCreate(
            ['vault_id' => $clientVault->id, 'name' => 'Acme Corp'],
            [
                'description' => 'Project work for Acme Corporation',
                'is_default' => true,
                'sort_order' => 1,
            ]
        );

        // Create demo types if they don't exist
        $todoType = Type::firstOrCreate(
            ['value' => 'todo'],
            ['label' => 'Todo', 'color' => 'blue']
        );

        $meetingType = Type::firstOrCreate(
            ['value' => 'meeting'],
            ['label' => 'Meeting', 'color' => 'green']
        );

        $ideaType = Type::firstOrCreate(
            ['value' => 'idea'],
            ['label' => 'Idea', 'color' => 'purple']
        );

        // Create demo routing rules
        VaultRoutingRule::firstOrCreate(
            ['name' => 'Meeting Keywords'],
            [
                'match_type' => 'keyword',
                'match_value' => 'meeting',
                'target_vault_id' => $workVault->id,
                'target_project_id' => $workProject->id,
                'priority' => 10,
                'is_active' => true,
                'notes' => 'Route fragments containing "meeting" to work vault',
            ]
        );

        VaultRoutingRule::firstOrCreate(
            ['name' => 'Client Communications'],
            [
                'match_type' => 'keyword',
                'match_value' => 'client',
                'target_vault_id' => $clientVault->id,
                'target_project_id' => $clientProject->id,
                'priority' => 5,
                'is_active' => true,
                'notes' => 'Route client-related fragments to clients vault',
            ]
        );

        VaultRoutingRule::firstOrCreate(
            ['name' => 'Todo Type Routing'],
            [
                'match_type' => 'type',
                'match_value' => 'todo',
                'target_vault_id' => $personalVault->id,
                'target_project_id' => $personalProject->id,
                'priority' => 20,
                'is_active' => true,
                'notes' => 'Route todo-type fragments to personal vault',
            ]
        );

        VaultRoutingRule::firstOrCreate(
            ['name' => 'Urgent Tag Routing'],
            [
                'match_type' => 'tag',
                'match_value' => 'urgent',
                'target_vault_id' => $workVault->id,
                'target_project_id' => $workProject->id,
                'priority' => 1,
                'is_active' => true,
                'notes' => 'High priority routing for urgent-tagged fragments',
            ]
        );

        VaultRoutingRule::firstOrCreate(
            ['name' => 'Phone Number Detection'],
            [
                'match_type' => 'regex',
                'match_value' => '(\+\d{1,3}\s?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}',
                'target_vault_id' => $clientVault->id,
                'target_project_id' => $clientProject->id,
                'priority' => 15,
                'is_active' => true,
                'notes' => 'Route fragments containing phone numbers to clients vault',
            ]
        );

        $this->command->info('Demo routing data seeded successfully!');
        $this->command->info("Created vaults: {$workVault->name}, {$personalVault->name}, {$clientVault->name}");
        $this->command->info('Created '.VaultRoutingRule::count().' routing rules');
    }
}
