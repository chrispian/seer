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
        // Add performance indexes for todo queries using JSON extraction (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            // Index for todo status queries (most common)
            DB::statement("CREATE INDEX IF NOT EXISTS idx_fragments_todo_status ON fragments ((state::jsonb->>'status')) WHERE type::text = 'todo'");

            // Index for todo priority queries
            DB::statement("CREATE INDEX IF NOT EXISTS idx_fragments_todo_priority ON fragments ((state::jsonb->>'priority')) WHERE type::text = 'todo'");

            // Index for due date filtering (as text for simplicity)
            DB::statement("CREATE INDEX IF NOT EXISTS idx_fragments_todo_due_date ON fragments ((state::jsonb->>'due_at')) WHERE type::text = 'todo' AND (state::jsonb->>'due_at') IS NOT NULL");

            // Composite index for status and priority (common combination)
            DB::statement("CREATE INDEX IF NOT EXISTS idx_fragments_todo_status_priority ON fragments ((state::jsonb->>'status'), (state::jsonb->>'priority')) WHERE type::text = 'todo'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_fragments_todo_status');
            DB::statement('DROP INDEX IF EXISTS idx_fragments_todo_priority');
            DB::statement('DROP INDEX IF EXISTS idx_fragments_todo_due_date');
            DB::statement('DROP INDEX IF EXISTS idx_fragments_todo_status_priority');
        }
    }
};
