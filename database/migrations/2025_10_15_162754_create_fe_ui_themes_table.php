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
        Schema::create('fe_ui_themes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('e.g., theme.default, theme.halloween2025');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('design_tokens_json')->comment('radius, spacing, colors, typography');
            $table->json('tailwind_overrides_json')->nullable()->comment('custom Tailwind config');
            $table->json('variants_json')->nullable()->comment('light/dark/accessible variants');
            $table->string('version', 20);
            $table->string('hash', 64);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('key');
            $table->index('enabled');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_ui_themes');
    }
};
