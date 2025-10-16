<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fe_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., Invoice, Contact
            $table->string('version')->default('1.0.0');
            $table->json('meta_json')->nullable(); // capabilities, validation policies
            $table->json('options_json')->nullable(); // materialize, persistence, adapter hints
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('fe_types');
    }
};
