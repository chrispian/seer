<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the unified commands table for all command routing (slash/CLI/MCP).
     * Commands are the controller layer that operate on types.
     */
    public function up(): void
    {
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->string('command')->unique()->comment('Unique identifier: /sprints, orchestration:sprints, etc.');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable()->comment('Orchestration, Navigation, Admin, etc.');
            
            // Data Binding
            $table->string('type_slug', 100)->nullable()->comment('FK to types_registry.slug');
            $table->string('handler_class')->comment('PHP class to execute: App\\Commands\\Sprint\\ListCommand');
            
            // Availability Flags - Controls where command appears
            $table->boolean('available_in_slash')->default(false);
            $table->boolean('available_in_cli')->default(false);
            $table->boolean('available_in_mcp')->default(false);
            
            // UI Configuration (NULL = no UI, e.g., for CLI-only commands)
            $table->string('ui_modal_container', 100)->nullable()->comment('DataManagementModal, Dialog, Drawer');
            $table->string('ui_layout_mode', 50)->nullable()->comment('list, grid, table');
            $table->string('ui_card_component')->nullable()->comment('Override type default card component');
            $table->string('ui_detail_component')->nullable()->comment('Override type default detail component');
            
            // View Configuration
            $table->json('filters')->nullable()->comment('Command-specific filters');
            $table->json('default_sort')->nullable()->comment('Default sorting config');
            $table->integer('pagination_default')->default(25);
            
            // Metadata
            $table->integer('usage_count')->default(0)->comment('Track command popularity');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('type_slug')->references('slug')->on('types_registry')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('category');
            $table->index(['available_in_slash', 'is_active']);
            $table->index(['available_in_cli', 'is_active']);
            $table->index(['available_in_mcp', 'is_active']);
            $table->index(['type_slug', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
