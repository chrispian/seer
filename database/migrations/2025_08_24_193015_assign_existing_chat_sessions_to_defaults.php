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
        // Get the default vault and project IDs
        $defaultVaultId = DB::table('vaults')->where('is_default', true)->value('id');
        $defaultProjectId = DB::table('projects')->where('is_default', true)->value('id');

        // Update all chat sessions that don't have vault_id or project_id set
        DB::table('chat_sessions')
            ->whereNull('vault_id')
            ->orWhereNull('project_id')
            ->update([
                'vault_id' => $defaultVaultId,
                'project_id' => $defaultProjectId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set vault_id and project_id back to null for sessions that were updated
        $defaultVaultId = DB::table('vaults')->where('is_default', true)->value('id');
        $defaultProjectId = DB::table('projects')->where('is_default', true)->value('id');

        DB::table('chat_sessions')
            ->where('vault_id', $defaultVaultId)
            ->where('project_id', $defaultProjectId)
            ->update([
                'vault_id' => null,
                'project_id' => null,
                'updated_at' => now(),
            ]);
    }
};
