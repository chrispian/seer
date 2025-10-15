<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_type_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fe_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('label')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('searchable')->default(false);
            $table->boolean('sortable')->default(false);
            $table->boolean('filterable')->default(false);
            $table->json('validation')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['fe_type_id', 'name']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_type_fields');
    }
};
