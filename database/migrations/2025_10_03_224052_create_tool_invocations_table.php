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
        Schema::create('tool_invocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('tool_slug');
            $table->string('command_slug')->nullable();
            $table->unsignedBigInteger('fragment_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('status')->default('ok'); // ok|error
            $table->float('duration_ms')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            // Indexes for querying
            $table->index(['tool_slug', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_invocations');
    }
};
