<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('prompt_registry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind'); // system|tool|style|user
            $table->longText('text');
            $table->json('variables')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('tags')->nullable();
            $table->uuid('owner_id')->nullable()->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('prompt_registry'); }
};
