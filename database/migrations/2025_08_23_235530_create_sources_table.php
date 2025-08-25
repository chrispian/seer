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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique()->comment('Source key: obsidian, web, clipboard, youtube, etc.');
            $table->string('label', 128)->comment('Human readable label');
            $table->json('meta')->nullable()->comment('Source-specific metadata');
            $table->timestamps();
        });

        // Add source_key field to fragments table
        Schema::table('fragments', function (Blueprint $table) {
            $table->string('source_key', 64)->nullable()->after('source')->comment('Standardized source identifier');
            $table->index('source_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropIndex(['source_key']);
            $table->dropColumn('source_key');
        });
        
        Schema::dropIfExists('sources');
    }
};
