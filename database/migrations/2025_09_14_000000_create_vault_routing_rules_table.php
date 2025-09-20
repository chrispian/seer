<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('match_type')->default('keyword');
            $table->string('match_value')->nullable();
            $table->json('conditions')->nullable();
            $table->foreignId('target_vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->foreignId('target_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('scope_vault_id')->nullable()->constrained('vaults')->nullOnDelete();
            $table->foreignId('scope_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['priority', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_routing_rules');
    }
};
