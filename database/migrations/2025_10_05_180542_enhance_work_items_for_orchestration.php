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
            // Add orchestration fields to existing work_items table
            $table->string('delegation_status')->default('unassigned')->comment('Orchestration status: unassigned, assigned, in_progress, blocked, completed');
            $table->json('delegation_context')->nullable()->comment('Assignment context and notes');
            $table->json('delegation_history')->nullable()->comment('Track assignment changes and progress');
            $table->decimal('estimated_hours', 8, 2)->nullable()->comment('Task estimation in hours');
            $table->decimal('actual_hours', 8, 2)->nullable()->comment('Actual time spent in hours');

            // Add index for delegation status queries
            $table->index('delegation_status', 'work_items_delegation_status_idx');

            // Add composite index for assignee queries (if not exists)
            $table->index(['assignee_type', 'assignee_id'], 'work_items_assignee_idx');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropIndex('work_items_delegation_status_idx');
            $table->dropIndex('work_items_assignee_idx');
            $table->dropColumn([
                'delegation_status',
                'delegation_context',
                'delegation_history',
                'estimated_hours',
                'actual_hours',
            ]);
        });
    }
};
