<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->foreignId('vault_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('vault_id')->constrained()->nullOnDelete();
            $table->boolean('is_pinned')->default(false)->after('is_active');
            $table->integer('sort_order')->default(0)->after('is_pinned');

            $table->index(['vault_id', 'project_id', 'is_pinned']);
            $table->index(['is_pinned', 'sort_order']);
            $table->index(['vault_id', 'is_active', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['vault_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['vault_id', 'project_id', 'is_pinned', 'sort_order']);
        });
    }
};
