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
        Schema::create('orchestration_artifacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->index();
            $table->string('hash', 64)->index();
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size_bytes')->unsigned();
            $table->json('metadata')->nullable();
            $table->string('fe_uri');
            $table->string('storage_path');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('work_items')->cascadeOnDelete();

            $table->unique(['task_id', 'filename']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orchestration_artifacts');
    }
};
