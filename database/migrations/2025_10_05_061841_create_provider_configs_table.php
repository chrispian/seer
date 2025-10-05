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
        Schema::create('provider_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->boolean('enabled')->default(true);
            $table->json('ui_preferences')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('rate_limits')->nullable();
            $table->integer('usage_count')->default(0);
            $table->decimal('total_cost', 10, 6)->default(0);
            $table->timestamp('last_health_check')->nullable();
            $table->json('health_status')->nullable();
            $table->integer('priority')->default(50);
            $table->timestamps();

            // Indexes for performance
            $table->index('provider');
            $table->index('enabled');
            $table->index(['enabled', 'priority']);
            $table->index('last_health_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_configs');
    }
};
