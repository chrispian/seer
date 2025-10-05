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
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->string('model_id')->index(); // ID from models.dev API
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('capabilities')->nullable(); // text, embedding, vision, etc.
            $table->json('pricing')->nullable(); // input/output token costs
            $table->json('limits')->nullable(); // context length, rate limits
            $table->string('logo_url')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(50);
            $table->json('metadata')->nullable(); // additional API data
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'model_id']);
            $table->index(['enabled', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};
