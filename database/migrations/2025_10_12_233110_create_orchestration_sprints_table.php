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
        Schema::create('orchestration_sprints', function (Blueprint $table) {
            $table->id();
            $table->string('sprint_code')->unique();
            $table->string('title');
            $table->enum('status', ['planning', 'active', 'completed', 'on_hold'])->default('planning');
            $table->string('owner')->nullable();
            $table->string('hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sprint_code');
            $table->index('status');
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orchestration_sprints');
    }
};
