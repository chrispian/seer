<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('agent_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_id')->nullable()->index();
            $table->string('topic');
            $table->longText('body');
            $table->string('kind');
            $table->string('scope');
            $table->timestamp('ttl_at')->nullable();
            $table->json('links')->nullable();
            $table->json('tags')->nullable();
            $table->json('provenance')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_vectors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->json('embedding'); // float array
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('topic');
            $table->text('decision');
            $table->text('rationale')->nullable();
            $table->json('alternatives')->nullable();
            $table->float('confidence')->nullable();
            $table->json('links')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('agent_decisions');
        Schema::dropIfExists('agent_vectors');
        Schema::dropIfExists('agent_notes');
    }
};
