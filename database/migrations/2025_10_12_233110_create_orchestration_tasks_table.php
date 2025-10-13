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
        Schema::create('orchestration_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->nullable()->constrained('orchestration_sprints')->nullOnDelete();
            $table->string('task_code')->unique();
            $table->string('title');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $table->enum('priority', ['P0', 'P1', 'P2', 'P3'])->default('P2');
            $table->integer('phase')->nullable();
            $table->string('hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->json('agent_config')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_code');
            $table->index('sprint_id');
            $table->index('status');
            $table->index('priority');
            $table->index('phase');
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orchestration_tasks');
    }
};
