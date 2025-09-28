<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->unsignedBigInteger('vault_id')->nullable()->after('fragment_ids');
            $table->unsignedBigInteger('project_id')->nullable()->after('vault_id');
            
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            
            $table->index(['vault_id', 'project_id', 'last_viewed_at'], 'bookmarks_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropForeign(['vault_id']);
            $table->dropForeign(['project_id']);
            $table->dropIndex('bookmarks_scope_idx');
            $table->dropColumn(['vault_id', 'project_id']);
        });
    }
};
