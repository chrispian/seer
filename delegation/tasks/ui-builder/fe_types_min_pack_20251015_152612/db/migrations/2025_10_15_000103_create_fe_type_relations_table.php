<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fe_type_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fe_type_id')->constrained('fe_types')->cascadeOnDelete();
            $table->string('name'); // company, lines
            $table->string('relation'); // belongsTo, hasMany, morphTo, etc.
            $table->string('target'); // Company, InvoiceLine
            $table->json('options_json')->nullable(); // fk, local key, pivot, etc.
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['fe_type_id','name']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('fe_type_relations');
    }
};
