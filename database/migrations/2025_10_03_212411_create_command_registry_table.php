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
        Schema::create('command_registry', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('version')->default('1.0.0');
            $table->string('source_path');
            $table->string('steps_hash');
            $table->json('capabilities')->nullable();
            $table->json('requires_secrets')->nullable();
            $table->boolean('reserved')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['slug', 'version']);
            $table->index('steps_hash');
            $table->index('reserved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_registry');
    }
};
