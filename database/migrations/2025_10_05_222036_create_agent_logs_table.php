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
        Schema::create('agent_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('source_type', ['opencode', 'claude_desktop', 'claude_mcp'])->index();
            $table->string('source_file')->comment('Original log file name');
            $table->timestamp('file_modified_at')->comment('When the source file was last modified');
            $table->string('log_level', 20)->nullable()->index();
            $table->timestamp('log_timestamp')->index()->comment('Original timestamp from log entry');
            $table->string('service', 100)->nullable()->index();
            $table->text('message')->nullable();
            $table->jsonb('structured_data')->nullable()->comment('Parsed structured log data');
            $table->string('session_id', 100)->nullable()->index();
            $table->string('provider', 50)->nullable()->index();
            $table->string('model', 100)->nullable()->index();
            $table->jsonb('tool_calls')->nullable()->comment('Tool call data if present');
            $table->uuid('work_item_id')->nullable()->index();
            $table->string('file_checksum', 64)->index()->comment('SHA256 of source file for deduplication');
            $table->bigInteger('file_line_number')->nullable()->comment('Line number in source file');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('work_item_id')->references('id')->on('work_items')->onDelete('set null');

            // Composite indexes for performance
            $table->index(['log_timestamp', 'source_type']);
            $table->index(['session_id', 'log_timestamp']);
            $table->index(['provider', 'model', 'log_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_logs');
    }
};
