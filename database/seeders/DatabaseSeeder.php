<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use App\Models\SeerLog;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // SeerLog::factory(10)->create();

        // Setup system essentials first
        $this->call([
            TypesSeeder::class,                 // Unified types registry (NEW)
            CommandsSeeder::class,              // Unified commands (NEW)
            SystemTypesSeeder::class,           // Essential fragment types
            ToolDefinitionsSeeder::class,       // AI tool definitions
            DefaultVaultProjectSeeder::class,   // Default vault and project
            // TypeSeeder::class,               // OLD - replaced by TypesSeeder
            // DemoRoutingDataSeeder::class,    // REMOVED - archived to docs/demo-seeder-backup
            // \Database\Seeders\Demo\DemoDataSeeder::class, // REMOVED - archived to docs/demo-seeder-backup
        ]);
    }
}
