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
            $table->text('agent_content')->nullable()->comment('Content from AGENT.md file');
            $table->text('plan_content')->nullable()->comment('Content from PLAN.md file');
            $table->text('context_content')->nullable()->comment('Content from CONTEXT.md file');
            $table->text('todo_content')->nullable()->comment('Content from TODO.md file');
            $table->text('summary_content')->nullable()->comment('Content from IMPLEMENTATION_SUMMARY.md file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn([
                'agent_content',
                'plan_content',
                'context_content',
                'todo_content',
                'summary_content',
            ]);
        });
    }
};
