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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type'); // 'command', 'file_operation', 'network', 'tool_call'
            $table->text('operation_summary'); // Short description for chat
            $table->json('operation_details'); // Full details
            $table->integer('risk_score'); // 0-100
            $table->string('risk_level'); // low/medium/high/critical
            $table->json('risk_factors'); // Contributing factors
            $table->json('dry_run_result')->nullable(); // Simulation results
            $table->unsignedBigInteger('fragment_id')->nullable(); // Link to fragment for long content
            $table->string('status')->default('pending'); // pending/approved/rejected/timeout
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_method')->nullable(); // 'button_click' or 'natural_language'
            $table->text('user_message')->nullable(); // Natural language approval/rejection
            $table->string('conversation_id')->nullable(); // Link to chat session
            $table->string('message_id')->nullable(); // Link to chat message
            $table->timestamp('timeout_at')->nullable(); // Auto-reject after 5 minutes
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index('timeout_at');
            $table->foreign('fragment_id')->references('id')->on('fragments')->onDelete('set null');
            $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
