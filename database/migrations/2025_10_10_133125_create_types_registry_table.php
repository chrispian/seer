<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the unified types_registry table for both model-backed and fragment-backed types.
     * This replaces fragment_type_registry with a more flexible architecture.
     */
    public function up(): void
    {
        Schema::create('types_registry', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('display_name');
            $table->string('plural_name');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 7)->nullable();
            
            // Data Layer - KEY INNOVATION: storage_type distinguishes model vs fragment-backed
            $table->enum('storage_type', ['model', 'fragment'])->default('fragment');
            $table->string('model_class')->nullable()->comment('For model-backed types: App\\Models\\Sprint');
            $table->json('schema')->nullable()->comment('For fragment-backed types: JSON schema definition');
            
            // Display Defaults
            $table->string('default_card_component')->nullable()->comment('SprintCard, NoteCard, etc.');
            $table->string('default_detail_component')->nullable()->comment('SprintDetailModal, UnifiedDetailModal, etc.');
            
            // Capabilities & Configuration
            $table->json('capabilities')->nullable()->comment('["searchable", "filterable", "sortable", "taggable"]');
            $table->json('hot_fields')->nullable()->comment('Quick access fields like ["title", "status"]');
            
            // System Flags
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_system')->default(false)->comment('System types cannot be deleted');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('storage_type');
            $table->index('is_enabled');
            $table->index(['is_enabled', 'storage_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types_registry');
    }
};
