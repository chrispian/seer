<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add FULLTEXT index for search (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fragments ADD FULLTEXT fulltext_search (title, message)');
        }

        // Add index for hybrid ranking components
        Schema::table('fragments', function (Blueprint $table) {
            $table->index('created_at', 'idx_created_at');
            $table->index('type', 'idx_type');
            $table->index(['vault', 'project_id'], 'idx_vault_project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FULLTEXT index (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fragments DROP INDEX fulltext_search');
        }

        // Drop other indexes
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_type');
            $table->dropIndex('idx_vault_project');
        });
    }
};
