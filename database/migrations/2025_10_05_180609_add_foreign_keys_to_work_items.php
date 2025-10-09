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
        Schema::table('work_items', function (Blueprint $table) {
            // Add foreign key for parent_id (self-referencing)
            $table->foreign('parent_id', 'work_items_parent_id_foreign')
                ->references('id')->on('work_items')
                ->onDelete('set null');

            // Note: project_id foreign key not added due to type mismatch
            // work_items.project_id is uuid, but projects.id is bigint
            // This should be addressed in a future migration if needed
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropForeign('work_items_parent_id_foreign');
        });
    }
};
