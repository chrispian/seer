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
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_type'); // tool, command, path, domain
            $table->string('category')->nullable(); // e.g., 'shell', 'filesystem', 'network'
            $table->string('pattern'); // The pattern to match (e.g., 'shell', '*.github.com', '/workspace/*')
            $table->enum('action', ['allow', 'deny'])->default('deny');
            $table->integer('priority')->default(100); // Lower = higher priority (deny rules typically lower)
            $table->json('metadata')->nullable(); // Additional context (risk_weight, timeout, etc.)
            $table->text('description')->nullable(); // Human-readable description
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable(); // User who created this rule
            $table->string('updated_by')->nullable(); // User who last updated
            $table->timestamps();

            // Indexes
            $table->index(['policy_type', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->index(['action', 'priority']);
            $table->unique(['policy_type', 'category', 'pattern', 'action'], 'unique_policy_rule');
        });

        Schema::create('security_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('version_number');
            $table->json('policies_snapshot'); // Full snapshot of all policies at this version
            $table->string('created_by')->nullable();
            $table->text('change_notes')->nullable();
            $table->timestamp('created_at');

            $table->index('version_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};
