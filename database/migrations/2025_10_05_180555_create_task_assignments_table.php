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
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_item_id')->comment('References work_items.id');
            $table->uuid('agent_id')->comment('References agent_profiles.id');
            $table->unsignedBigInteger('assigned_by')->nullable()->comment('References users.id - who made the assignment');
            $table->timestamp('assigned_at')->useCurrent()->comment('When the assignment was made');
            $table->timestamp('started_at')->nullable()->comment('When work actually began');
            $table->timestamp('completed_at')->nullable()->comment('When work was completed');
            $table->string('status')->default('assigned')->comment('Status: assigned, started, paused, completed, cancelled');
            $table->text('notes')->nullable()->comment('Assignment notes and updates');
            $table->json('context')->nullable()->comment('Assignment-specific context and metadata');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('work_item_id')->references('id')->on('work_items')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('agent_profiles')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for common queries
            $table->index('work_item_id', 'task_assignments_work_item_idx');
            $table->index('agent_id', 'task_assignments_agent_idx');
            $table->index('status', 'task_assignments_status_idx');
            $table->index('assigned_at', 'task_assignments_assigned_at_idx');
            $table->index(['agent_id', 'status'], 'task_assignments_agent_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
