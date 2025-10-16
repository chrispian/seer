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
        Schema::create('fe_ui_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->integer('percentage')->nullable();
            $table->json('conditions')->nullable();
            $table->json('metadata')->nullable();
            $table->string('environment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('key');
            $table->index(['is_enabled', 'environment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_ui_feature_flags');
    }
};
