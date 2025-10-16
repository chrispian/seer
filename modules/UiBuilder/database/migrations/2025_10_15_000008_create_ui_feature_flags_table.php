<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(false);
            $table->json('conditions_json')->nullable();
            $table->timestamps();
            
            $table->index('key');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_feature_flags');
    }
};
