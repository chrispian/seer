<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_components', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type');
            $table->string('kind')->nullable();
            $table->json('config')->nullable();
            $table->string('variant')->nullable();
            $table->json('schema_json')->nullable();
            $table->json('defaults_json')->nullable();
            $table->json('capabilities_json')->nullable();
            $table->string('hash', 64)->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            
            $table->index('type');
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_components');
    }
};
