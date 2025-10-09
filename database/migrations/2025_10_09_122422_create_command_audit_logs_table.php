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
        Schema::create('command_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command_name');
            $table->text('command_signature')->nullable();
            $table->json('arguments')->nullable();
            $table->json('options')->nullable();
            $table->string('status')->default('pending');
            $table->integer('exit_code')->nullable();
            $table->text('output')->nullable();
            $table->text('error_output')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->boolean('is_destructive')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['command_name', 'created_at']);
            $table->index(['is_destructive', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_audit_logs');
    }
};
