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
        Schema::create('fragment_links', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->bigInteger('from_id', false, true)->comment('Source fragment ID');
            $table->bigInteger('to_id', false, true)->comment('Target fragment ID');
            $table->enum('relation', [
                'similar_to',
                'refines',
                'cluster_member',
                'duplicate_of',
                'references',
                'mentions',
                'child_of'
            ])->comment('Type of relationship between fragments');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['from_id', 'relation']);
            $table->index(['to_id', 'relation']);
            
            // Foreign key constraints
            $table->foreign('from_id')->references('id')->on('fragments')->cascadeOnDelete();
            $table->foreign('to_id')->references('id')->on('fragments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fragment_links');
    }
};
