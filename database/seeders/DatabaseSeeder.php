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
            SystemTypesSeeder::class,           // Essential fragment types
            ToolDefinitionsSeeder::class,       // AI tool definitions
            DefaultVaultProjectSeeder::class,   // Default vault and project
            TypeSeeder::class,                  // Basic fragment types
            DemoRoutingDataSeeder::class,       // Demo data (development only)
            \Database\Seeders\Demo\DemoDataSeeder::class, // Demo dataset
        ]);
    }
}
