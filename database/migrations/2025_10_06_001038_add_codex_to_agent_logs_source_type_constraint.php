<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing constraint
        DB::statement('ALTER TABLE agent_logs DROP CONSTRAINT agent_logs_source_type_check');
        
        // Add new constraint with 'codex' included
        DB::statement("ALTER TABLE agent_logs ADD CONSTRAINT agent_logs_source_type_check CHECK (source_type IN ('opencode', 'claude_desktop', 'claude_mcp', 'codex'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop updated constraint
        DB::statement('ALTER TABLE agent_logs DROP CONSTRAINT agent_logs_source_type_check');
        
        // Restore original constraint without 'codex'
        DB::statement("ALTER TABLE agent_logs ADD CONSTRAINT agent_logs_source_type_check CHECK (source_type IN ('opencode', 'claude_desktop', 'claude_mcp'))");
    }
};
