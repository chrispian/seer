<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_type_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fe_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('related_type');
            $table->string('foreign_key')->nullable();
            $table->string('local_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['fe_type_id', 'name']);
            $table->index('related_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_type_relations');
    }
};
