<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fragment_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fragment_id')->constrained()->cascadeOnDelete();
            $table->string('provider');          // e.g., openai, ollama
            $table->unsignedInteger('dims');     // e.g., 1536
            $table->timestamps();

            $table->unique(['fragment_id', 'provider']); // allow multiple providers, 1 each
        });

        // Add vector column (Laravel has no native 'vector' type)
        DB::statement('ALTER TABLE fragment_embeddings ADD COLUMN embedding vector(1536)');

        // Optional ANN index (use after you have some rows; tweak lists later)
        // DB::statement("CREATE INDEX fragment_embeddings_ivfflat
        //     ON fragment_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)");
    }

    public function down(): void
    {
        Schema::dropIfExists('fragment_embeddings');
    }
};
