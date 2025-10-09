<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMigrationsCommand extends Command
{
    protected $signature = 'migrations:sync {--dry-run : Show what would be done without doing it}';
    protected $description = 'Sync migrations table with actual database state';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get current max batch
        $nextBatch = DB::table('migrations')->max('batch') + 1;
        $this->info("Next batch number: {$nextBatch}");

        // List of migrations to mark as run (all that have already been applied to DB)
        $missingMigrations = [
            // Already synced in batch 8 - kept for reference
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
            '2024_10_04_create_telemetry_tables',
            '20251004151938_create_saved_queries_table',
            '20251004151939_create_prompt_registry_table',
            '20251004151940_create_work_items_tables',
            '20251004151941_create_sprints_tables',
            '20251004151942_create_agent_memory_tables',
            
            // Additional migrations from restore
            '2025_09_20_160611_add_toast_preferences_to_users_table',
            '2025_09_20_175034_add_model_metadata_to_fragments_table',
            '2025_09_20_175042_add_model_metadata_to_chat_sessions_table',
            '2025_09_20_233602_create_a_i_credentials_table',
            '2025_09_28_220620_enhance_fragments_telemetry',
            '2025_09_28_220625_add_session_scoping_to_bookmarks',
            '2025_10_03_211248_create_fragment_type_registry_table',
            '2025_10_03_211744_add_todo_hot_fields_to_fragments_table',
            '2025_10_03_212411_create_command_registry_table',
            '2025_10_03_214131_create_schedules_table',
            '2025_10_03_214147_create_schedule_runs_table',
            '2025_10_03_215250_add_inbox_fields_to_fragments',
            '2025_10_03_221525_create_pipeline_metrics_tables',
            '2025_10_03_224052_create_tool_invocations_table',
            '2025_10_04_030244_add_profile_fields_to_users_table',
            '2025_10_04_151937_create_artifacts_table',
            '2025_10_05_000001_add_sqlite_vector_support',
            '2025_10_05_000002_create_sqlite_fts5_support',
            '2025_10_05_040730_add_vector_indexes_optimization',
            '2025_10_05_061506_create_ai_credentials_table',
            '2025_10_05_061841_create_provider_configs_table',
            '2025_10_05_061908_enhance_ai_credentials_table',
            '2025_10_05_062056_populate_provider_configs_from_credentials',
            '2025_10_05_133347_rename_provider_configs_table_to_providers',
            '2025_10_05_133404_create_models_table',
            '2025_10_05_133709_add_new_columns_to_providers_table',
            '2025_10_05_180528_create_agent_profiles_table',
            '2025_10_05_180542_enhance_work_items_for_orchestration',
            
            // Already synced
            '2025_01_06_000001_seed_obsidian_source',
            '2025_10_05_180555_create_task_assignments_table',
            '2025_10_05_180609_add_foreign_keys_to_work_items',
            '2025_10_05_215240_add_content_fields_to_work_items_table',
            '2025_10_05_220836_add_pr_url_to_work_items_table',
            '2025_10_05_222036_create_agent_logs_table',
            '2025_10_06_001038_add_codex_to_agent_logs_source_type_constraint',
            '2025_10_06_002123_add_claude_projects_to_agent_logs_source_type_constraint',
            '2025_10_06_210929_create_messages_table',
            '2025_10_06_211016_create_orchestration_artifacts_table',
            '2025_10_06_230300_seed_chatgpt_sources',
            '2025_10_07_000100_seed_readwise_source',
            '2025_10_07_001827_create_agents_table',
            '2025_10_07_011023_add_avatar_to_agents_table',
            '2025_10_07_055556_create_task_activities_table',
            '2025_10_07_063016_add_type_management_columns_to_fragment_type_registry_table',
            '2025_10_07_063956_create_documentation_table',
            '2025_10_07_073618_add_ui_component_columns_to_fragment_type_registry_table',
            '2025_10_07_074624_add_detail_component_to_fragment_type_registry_table',
            '2025_10_09_031557_create_tool_definitions_table',
        ];

        $this->info("\n📋 Migrations to mark as run: " . count($missingMigrations));

        $marked = 0;
        $skipped = 0;

        foreach ($missingMigrations as $migration) {
            // Check if already marked
            $exists = DB::table('migrations')
                ->where('migration', $migration)
                ->exists();

            if ($exists) {
                $this->line("  ⏭️  Skip: {$migration} (already marked)");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $nextBatch
                ]);
                $this->info("  ✅ Marked: {$migration}");
                $marked++;
            } else {
                $this->line("  📝 Would mark: {$migration}");
                $marked++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("🔍 Dry run complete.");
            $this->info("Would mark {$marked} migrations, skip {$skipped} already marked.");
            $this->info("Run without --dry-run to apply changes.");
        } else {
            $this->info("✅ Migration sync complete!");
            $this->info("Marked {$marked} migrations, skipped {$skipped} already marked.");
            $this->info("You can now run 'php artisan migrate' for any future migrations.");
        }

        return 0;
    }
}
