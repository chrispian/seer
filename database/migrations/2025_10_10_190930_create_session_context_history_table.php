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
        Schema::create('session_context_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            
            $table->enum('action', ['push', 'pop', 'switch']);
            $table->enum('context_type', ['project', 'sprint', 'task', 'subtask']);
            $table->string('context_id', 100);
            $table->json('context_data')->nullable();
            
            $table->timestamp('switched_at');
            $table->integer('duration_seconds')->nullable();
            
            $table->index('session_id');
            $table->index('context_id');
            
            $table->foreign('session_id')->references('id')->on('work_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_context_history');
    }
};
