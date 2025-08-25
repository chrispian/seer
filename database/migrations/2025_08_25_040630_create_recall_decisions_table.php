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
        Schema::create('recall_decisions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id', false, true);
            $table->string('query', 512);
            $table->json('parsed_query')->nullable();
            $table->integer('total_results');
            $table->bigInteger('selected_fragment_id', false, true)->nullable();
            $table->integer('selected_index')->nullable();
            $table->string('action', 32)->default('select');
            $table->json('context')->nullable();
            $table->datetime('decided_at');
            $table->timestamps();

            // Indexes for analytics queries
            $table->index(['user_id', 'decided_at']);
            $table->index(['selected_fragment_id', 'decided_at']);
            $table->index('action');
            $table->index('decided_at');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('selected_fragment_id')->references('id')->on('fragments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recall_decisions');
    }
};
