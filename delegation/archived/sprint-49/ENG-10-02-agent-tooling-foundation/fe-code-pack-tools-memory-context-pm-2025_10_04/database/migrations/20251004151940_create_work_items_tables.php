<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // epic|story|task|bug|spike|decision
            $table->uuid('parent_id')->nullable()->index();
            $table->string('assignee_type')->nullable(); // agent|user
            $table->uuid('assignee_id')->nullable()->index();
            $table->string('status')->default('backlog');
            $table->string('priority')->nullable();
            $table->uuid('project_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->json('state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('work_item_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_item_id')->index();
            $table->string('kind'); // comment|status_change|cot|tool_log|artifact_link
            $table->longText('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_events');
        Schema::dropIfExists('work_items');
    }
};
