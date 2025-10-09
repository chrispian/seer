<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('agent_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            
            $table->string('activity_type', 50);
            $table->string('action', 100);
            
            $table->text('description')->nullable();
            $table->jsonb('changes')->nullable();
            $table->jsonb('metadata')->nullable();
            
            $table->timestamp('created_at');
            
            $table->foreign('task_id')
                ->references('id')
                ->on('work_items')
                ->onDelete('cascade');
            
            $table->foreign('agent_id')
                ->references('id')
                ->on('agent_profiles')
                ->onDelete('set null');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->index(['task_id', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            $table->index(['agent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
