<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_types', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('source_type');
            $table->json('config')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            
            $table->index('alias');
            $table->index('source_type');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_types');
    }
};
