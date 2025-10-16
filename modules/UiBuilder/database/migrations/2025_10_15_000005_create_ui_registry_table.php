<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_registry', function (Blueprint $table) {
            $table->id();
            $table->string('component_type')->unique();
            $table->string('react_component');
            $table->string('category')->nullable();
            $table->json('props_schema')->nullable();
            $table->string('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            
            $table->index('component_type');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_registry');
    }
};
