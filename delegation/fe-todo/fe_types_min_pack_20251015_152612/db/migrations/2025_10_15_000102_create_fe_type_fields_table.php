<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fe_type_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fe_type_id')->constrained('fe_types')->cascadeOnDelete();
            $table->string('name'); // amount, status, issued_at
            $table->string('type'); // string, decimal:12,2, enum:..., datetime
            $table->boolean('required')->default(false);
            $table->boolean('unique')->default(false);
            $table->json('options_json')->nullable(); // default, enum values, casts, ui hints
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['fe_type_id','name']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('fe_type_fields');
    }
};
