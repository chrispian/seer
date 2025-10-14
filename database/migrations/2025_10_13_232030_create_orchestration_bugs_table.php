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
        Schema::create('orchestration_bugs', function (Blueprint $table) {
            $table->id();
            $table->string('bug_hash', 64)->unique();
            $table->string('task_code')->nullable();
            $table->string('error_message', 1000);
            $table->string('file_path')->nullable();
            $table->integer('line_number')->nullable();
            $table->text('stack_trace')->nullable();
            $table->json('context')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('bug_hash');
            $table->index('task_code');
            $table->index('created_at');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orchestration_bugs');
    }
};
