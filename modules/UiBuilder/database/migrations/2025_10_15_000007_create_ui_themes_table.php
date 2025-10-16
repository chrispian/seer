<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_themes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('tokens_json')->nullable();
            $table->string('parent_key')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            
            $table->index('is_default');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_themes');
    }
};
