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
        // Get the default project ID
        $defaultProjectId = DB::table('projects')->where('is_default', true)->value('id');

        // Update all fragments that don't have project_id set
        DB::table('fragments')
            ->whereNull('project_id')
            ->update([
                'project_id' => $defaultProjectId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set project_id back to null for fragments that were updated
        $defaultProjectId = DB::table('projects')->where('is_default', true)->value('id');

        DB::table('fragments')
            ->where('project_id', $defaultProjectId)
            ->update([
                'project_id' => null,
                'updated_at' => now(),
            ]);
    }
};
