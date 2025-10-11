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
        Schema::create('session_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            
            $table->enum('activity_type', ['command', 'context_update', 'note', 'artifact', 'pause', 'resume']);
            $table->string('command', 255)->nullable();
            $table->text('description')->nullable();
            
            $table->uuid('task_id')->nullable();
            $table->uuid('sprint_id')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamp('occurred_at');
            
            $table->index('session_id');
            $table->index('activity_type');
            $table->index('task_id');
            
            $table->foreign('session_id')->references('id')->on('work_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_activities');
    }
};
