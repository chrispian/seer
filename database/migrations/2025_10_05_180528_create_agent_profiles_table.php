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
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Human-readable agent name');
            $table->string('slug')->unique()->comment('Unique identifier for CLI/API reference');
            $table->string('type')->comment('Agent type: backend-engineer, frontend-engineer, etc.');
            $table->string('mode')->comment('Operation mode: implementation, planning, review, etc.');
            $table->text('description')->nullable()->comment('Agent description and purpose');
            $table->json('capabilities')->nullable()->comment('List of skills and tools');
            $table->json('constraints')->nullable()->comment('Limitations and operational rules');
            $table->json('tools')->nullable()->comment('Available tools and integrations');
            $table->json('metadata')->nullable()->comment('Flexible additional configuration');
            $table->string('status')->default('active')->comment('Status: active, inactive, archived');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['type', 'status'], 'agent_profiles_type_status_idx');
            $table->index('status', 'agent_profiles_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};
