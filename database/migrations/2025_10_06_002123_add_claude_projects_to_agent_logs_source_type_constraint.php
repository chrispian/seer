<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Drop existing constraint
        DB::statement('ALTER TABLE agent_logs DROP CONSTRAINT agent_logs_source_type_check');

        // Add new constraint with 'claude_projects' included
        DB::statement("ALTER TABLE agent_logs ADD CONSTRAINT agent_logs_source_type_check CHECK (source_type IN ('opencode', 'claude_desktop', 'claude_mcp', 'codex', 'claude_projects'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Drop updated constraint
        DB::statement('ALTER TABLE agent_logs DROP CONSTRAINT agent_logs_source_type_check');

        // Restore previous constraint without 'claude_projects'
        DB::statement("ALTER TABLE agent_logs ADD CONSTRAINT agent_logs_source_type_check CHECK (source_type IN ('opencode', 'claude_desktop', 'claude_mcp', 'codex'))");
    }
};
