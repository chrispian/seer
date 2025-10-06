<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stream')->index();
            $table->string('type');
            $table->uuid('task_id')->nullable()->index();
            $table->uuid('project_id')->nullable()->index();
            $table->uuid('to_agent_id')->nullable()->index();
            $table->uuid('from_agent_id')->nullable()->index();
            $table->json('headers')->nullable();
            $table->json('envelope');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('work_items')->nullOnDelete();
            $table->foreign('to_agent_id')->references('id')->on('agent_profiles')->nullOnDelete();
            $table->foreign('from_agent_id')->references('id')->on('agent_profiles')->nullOnDelete();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
