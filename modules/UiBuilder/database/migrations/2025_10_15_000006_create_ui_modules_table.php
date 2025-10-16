<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('manifest_json')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();
            
            $table->index('enabled');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_modules');
    }
};
