<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Vault;
use Illuminate\Database\Seeder;

class DefaultVaultProjectSeeder extends Seeder
{
    /**
     * Seed default vault and project if they don't exist.
     */
    public function run(): void
    {
        // Create default vault if none exists
        if (Vault::count() === 0) {
            $vault = Vault::create([
                'name' => 'Default',
                'description' => 'Default vault for fragments and chat sessions',
                'is_default' => true,
                'sort_order' => 1,
            ]);

            $this->command->info("Created default vault: {$vault->name} (ID: {$vault->id})");
        } else {
            // Ensure at least one vault is marked as default
            if (! Vault::where('is_default', true)->exists()) {
                $vault = Vault::first();
                $vault->update(['is_default' => true]);
                $this->command->info("Marked vault '{$vault->name}' as default");
            }
        }

        // Create default project if none exists
        if (Project::count() === 0) {
            $defaultVault = Vault::where('is_default', true)->first();

            $project = Project::create([
                'name' => 'General',
                'description' => 'Default project for general fragments and conversations',
                'vault_id' => $defaultVault->id,
                'is_default' => true,
                'sort_order' => 1,
            ]);

            $this->command->info("Created default project: {$project->name} (ID: {$project->id})");
        } else {
            // Ensure each vault has at least one default project
            $vaults = Vault::all();
            foreach ($vaults as $vault) {
                if (! $vault->projects()->where('is_default', true)->exists()) {
                    $project = $vault->projects()->first();
                    if ($project) {
                        $project->update(['is_default' => true]);
                        $this->command->info("Marked project '{$project->name}' as default for vault '{$vault->name}'");
                    }
                }
            }
        }
    }
}
