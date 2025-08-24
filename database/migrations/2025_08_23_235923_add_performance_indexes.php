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
        // Add FULLTEXT indexes for search
        DB::statement('ALTER TABLE fragments ADD FULLTEXT ft_frag_message (message)');
        
        // Add compound indexes for common query patterns
        Schema::table('fragments', function (Blueprint $table) {
            $table->index(['vault', 'type', 'created_at'], 'idx_frag_vault_type_created');
            $table->index(['workspace_id', 'created_at'], 'idx_frag_workspace_created');
            $table->index(['category_id', 'created_at'], 'idx_frag_category_created');
            $table->index(['importance', 'created_at'], 'idx_frag_importance_created');
        });
        
        // Add FULLTEXT index to file_text content if table exists
        if (Schema::hasTable('file_text')) {
            DB::statement('ALTER TABLE file_text ADD FULLTEXT ft_file_content (content)');
        }
        
        // Add partial index for normalized_url (TEXT column requires length specification)
        if (Schema::hasTable('links')) {
            DB::statement('ALTER TABLE links ADD INDEX idx_links_normalized_url (normalized_url(255))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop custom indexes
        if (Schema::hasTable('links')) {
            DB::statement('ALTER TABLE links DROP INDEX idx_links_normalized_url');
        }
        
        // Drop FULLTEXT indexes
        if (Schema::hasTable('file_text')) {
            DB::statement('ALTER TABLE file_text DROP INDEX ft_file_content');
        }
        
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropIndex('idx_frag_importance_created');
            $table->dropIndex('idx_frag_category_created');
            $table->dropIndex('idx_frag_workspace_created');
            $table->dropIndex('idx_frag_vault_type_created');
        });
        
        DB::statement('ALTER TABLE fragments DROP INDEX ft_frag_message');
    }
};
