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
        Schema::create('fragment_type_registry', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('version')->default('1.0.0');
            $table->string('source_path');
            $table->string('schema_hash');
            $table->json('hot_fields')->nullable();
            $table->json('capabilities')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['slug', 'version']);
            $table->index('schema_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fragment_type_registry');
    }
};
