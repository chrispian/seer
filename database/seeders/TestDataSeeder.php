<?php

namespace Database\Seeders;

use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\Project;
use App\Models\Type;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultRoutingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates comprehensive test data for various testing scenarios.
     * Only runs in testing environment to avoid polluting other environments.
     */
    public function run(): void
    {
        // Only run in testing environment
        if (! app()->environment('testing')) {
            $this->command->warn('TestDataSeeder only runs in testing environment');

            return;
        }

        DB::transaction(function () {
            $this->createTestUsers();
            $this->createTestVaultsAndProjects();
            $this->createTestTypes();
            $this->createTestRoutingRules();
            $this->createTestFragments();
            $this->createTestChatSessions();
        });

        $this->command->info('Test data seeded successfully!');
    }

    private function createTestUsers(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->command->info('Created test users');
    }

    private function createTestVaultsAndProjects(): void
    {
        // Work vault with multiple projects
        $workVault = Vault::factory()->create([
            'name' => 'work',
            'description' => 'Work-related content',
            'is_default' => false,
        ]);

        Project::factory()->create([
            'vault_id' => $workVault->id,
            'name' => 'Engineering',
            'description' => 'Software engineering tasks',
            'is_default' => true,
        ]);

        Project::factory()->create([
            'vault_id' => $workVault->id,
            'name' => 'Product',
            'description' => 'Product development tasks',
            'is_default' => false,
        ]);

        // Personal vault
        $personalVault = Vault::factory()->create([
            'name' => 'personal',
            'description' => 'Personal content',
            'is_default' => false,
        ]);

        Project::factory()->create([
            'vault_id' => $personalVault->id,
            'name' => 'Life',
            'description' => 'Personal life management',
            'is_default' => true,
        ]);

        // Clients vault
        $clientsVault = Vault::factory()->create([
            'name' => 'clients',
            'description' => 'Client communications',
            'is_default' => false,
        ]);

        Project::factory()->create([
            'vault_id' => $clientsVault->id,
            'name' => 'Acme Corp',
            'description' => 'Acme Corporation project',
            'is_default' => true,
        ]);

        $this->command->info('Created test vaults and projects');
    }

    private function createTestTypes(): void
    {
        $types = [
            ['value' => 'todo', 'label' => 'Todo', 'color' => 'blue'],
            ['value' => 'note', 'label' => 'Note', 'color' => 'gray'],
            ['value' => 'meeting', 'label' => 'Meeting', 'color' => 'green'],
            ['value' => 'idea', 'label' => 'Idea', 'color' => 'purple'],
            ['value' => 'observation', 'label' => 'Observation', 'color' => 'yellow'],
        ];

        foreach ($types as $type) {
            Type::firstOrCreate(['value' => $type['value']], $type);
        }

        $this->command->info('Created test types');
    }

    private function createTestRoutingRules(): void
    {
        $workVault = Vault::where('name', 'work')->first();
        $personalVault = Vault::where('name', 'personal')->first();
        $clientsVault = Vault::where('name', 'clients')->first();

        $workProject = Project::where('vault_id', $workVault->id)->where('name', 'Engineering')->first();
        $personalProject = Project::where('vault_id', $personalVault->id)->first();
        $clientProject = Project::where('vault_id', $clientsVault->id)->first();

        $rules = [
            [
                'name' => 'Meeting Keywords',
                'match_type' => 'keyword',
                'match_value' => 'meeting',
                'target_vault_id' => $workVault->id,
                'target_project_id' => $workProject->id,
                'priority' => 10,
            ],
            [
                'name' => 'Todo Type Routing',
                'match_type' => 'type',
                'match_value' => 'todo',
                'target_vault_id' => $personalVault->id,
                'target_project_id' => $personalProject->id,
                'priority' => 20,
            ],
            [
                'name' => 'Urgent Tag Routing',
                'match_type' => 'tag',
                'match_value' => 'urgent',
                'target_vault_id' => $workVault->id,
                'target_project_id' => $workProject->id,
                'priority' => 1,
            ],
            [
                'name' => 'Client Communications',
                'match_type' => 'keyword',
                'match_value' => 'client',
                'target_vault_id' => $clientsVault->id,
                'target_project_id' => $clientProject->id,
                'priority' => 5,
            ],
        ];

        foreach ($rules as $rule) {
            VaultRoutingRule::firstOrCreate(
                ['name' => $rule['name']],
                array_merge($rule, ['is_active' => true])
            );
        }

        $this->command->info('Created test routing rules');
    }

    private function createTestFragments(): void
    {
        $workVault = Vault::where('name', 'work')->first();
        $personalVault = Vault::where('name', 'personal')->first();

        // Basic fragments
        Fragment::factory()->count(5)->create();

        // Fragments with embeddings (AI-enabled scenario)
        Fragment::factory()
            ->count(3)
            ->withEmbeddings()
            ->withAIMetadata('openai', 'gpt-4o-mini')
            ->create();

        // Fragments without embeddings (AI-disabled scenario)
        Fragment::factory()
            ->count(3)
            ->withoutEmbeddings()
            ->create();

        // Complex fragments with entities
        Fragment::factory()
            ->count(2)
            ->withComplexContent()
            ->withEntities()
            ->withAIMetadata()
            ->create();

        // Todo fragments
        Fragment::factory()
            ->count(3)
            ->todo()
            ->withVault('personal')
            ->create();

        // Meeting fragments
        Fragment::factory()
            ->count(2)
            ->meeting()
            ->withVault('work')
            ->withAIMetadata()
            ->create();

        // Fragments for routing testing
        Fragment::factory()->create([
            'message' => 'Meeting with client about project requirements',
            'type' => 'meeting',
            'tags' => ['meeting', 'client'],
        ]);

        Fragment::factory()->create([
            'message' => 'TODO: Update the documentation for API endpoints',
            'type' => 'todo',
            'tags' => ['todo', 'urgent'],
        ]);

        Fragment::factory()->create([
            'message' => 'Client feedback on the latest prototype',
            'type' => 'note',
            'tags' => ['client', 'feedback'],
        ]);

        $this->command->info('Created test fragments');
    }

    private function createTestChatSessions(): void
    {
        // Active chat sessions
        ChatSession::factory()
            ->count(3)
            ->withMessages(5)
            ->create();

        // Pinned chat sessions
        ChatSession::factory()
            ->count(2)
            ->pinned()
            ->withConversation()
            ->create();

        // Chat session with custom name
        ChatSession::factory()
            ->withCustomName('project-planning')
            ->withMessages(10)
            ->withSummary()
            ->create();

        // Inactive chat sessions
        ChatSession::factory()
            ->count(2)
            ->inactive()
            ->withMessages(3)
            ->create();

        // OpenAI and Ollama chat sessions
        ChatSession::factory()
            ->openai()
            ->withConversation()
            ->create();

        ChatSession::factory()
            ->ollama()
            ->withMessages(7)
            ->create();

        $this->command->info('Created test chat sessions');
    }
}
