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
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_key', 100)->unique();
            
            $table->uuid('agent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chat_session_id')->nullable();
            
            $table->enum('source', ['cli', 'mcp', 'api', 'gui']);
            $table->enum('session_type', ['work', 'planning', 'review'])->default('work');
            $table->enum('status', ['active', 'paused', 'completed', 'abandoned'])->default('active');
            
            $table->json('context_stack');
            
            $table->uuid('active_project_id')->nullable();
            $table->uuid('active_sprint_id')->nullable();
            $table->uuid('active_task_id')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_active_seconds')->default(0);
            
            $table->text('summary')->nullable();
            $table->integer('tasks_completed')->default(0);
            $table->integer('artifacts_created')->default(0);
            
            $table->timestamps();
            
            $table->index('session_key');
            $table->index(['agent_id', 'status']);
            $table->index('active_task_id');
            $table->index('started_at');
            
            $table->foreign('agent_id')->references('id')->on('agent_profiles')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('set null');
            $table->foreign('active_sprint_id')->references('id')->on('sprints')->onDelete('set null');
            $table->foreign('active_task_id')->references('id')->on('work_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};
