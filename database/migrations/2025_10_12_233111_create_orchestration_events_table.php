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
        Schema::create('orchestration_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->enum('entity_type', ['sprint', 'task', 'agent', 'session']);
            $table->unsignedBigInteger('entity_id');
            $table->uuid('correlation_id')->nullable();
            $table->string('session_key')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('emitted_at');

            $table->index('event_type');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('correlation_id');
            $table->index('session_key');
            $table->index('emitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orchestration_events');
    }
};
