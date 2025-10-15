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
        Schema::table('orchestration_tasks', function (Blueprint $table) {
            $table->string('type')->nullable()->after('sprint_id')->comment('epic|story|task|bug|spike|decision');
            $table->unsignedBigInteger('parent_id')->nullable()->after('type')->index();
            $table->string('assignee_type')->nullable()->after('parent_id')->comment('agent|user');
            $table->uuid('assignee_id')->nullable()->after('assignee_type')->index();
            $table->uuid('project_id')->nullable()->after('assignee_id')->index();
            $table->json('tags')->nullable()->after('project_id');
            $table->json('state')->nullable()->after('tags');
            
            $table->string('delegation_status')->default('unassigned')->after('status')->comment('unassigned, assigned, in_progress, blocked, completed');
            $table->json('delegation_context')->nullable()->after('delegation_status')->comment('Assignment context and notes');
            $table->json('delegation_history')->nullable()->after('delegation_context')->comment('Track assignment changes and progress');
            
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('phase')->comment('Task estimation in hours');
            $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours')->comment('Actual time spent in hours');
            
            $table->text('agent_content')->nullable()->after('agent_config')->comment('Content from AGENT.md file');
            $table->text('plan_content')->nullable()->after('agent_content')->comment('Content from PLAN.md file');
            $table->text('context_content')->nullable()->after('plan_content')->comment('Content from CONTEXT.md file');
            $table->text('todo_content')->nullable()->after('context_content')->comment('Content from TODO.md file');
            $table->text('summary_content')->nullable()->after('todo_content')->comment('Content from IMPLEMENTATION_SUMMARY.md file');
            
            $table->string('pr_url')->nullable()->after('file_path')->comment('Pull request URL when task is completed');
            $table->timestamp('completed_at')->nullable()->after('pr_url')->comment('When the task was marked as completed');
            
            $table->index(['assignee_type', 'assignee_id'], 'orchestration_tasks_assignee_idx');
            $table->index('delegation_status', 'orchestration_tasks_delegation_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orchestration_tasks', function (Blueprint $table) {
            $table->dropIndex('orchestration_tasks_assignee_idx');
            $table->dropIndex('orchestration_tasks_delegation_status_idx');
            
            $table->dropColumn([
                'type',
                'parent_id',
                'assignee_type',
                'assignee_id',
                'project_id',
                'tags',
                'state',
                'delegation_status',
                'delegation_context',
                'delegation_history',
                'estimated_hours',
                'actual_hours',
                'agent_content',
                'plan_content',
                'context_content',
                'todo_content',
                'summary_content',
                'pr_url',
                'completed_at',
            ]);
        });
    }
};
