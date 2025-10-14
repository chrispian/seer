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
        Schema::create('fragment_tags', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true);
            $table->string('tag', 128);

            // Composite primary key
            $table->primary(['fragment_id', 'tag']);

            // Index for fast tag lookups and facet counts
            $table->index(['tag', 'fragment_id']);

            // Foreign key constraint
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fragment_tags');
    }
};
