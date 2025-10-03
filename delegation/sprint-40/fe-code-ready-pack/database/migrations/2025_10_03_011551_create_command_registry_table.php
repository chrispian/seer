<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('command_registry', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('slug')->unique();
            $t->string('version')->nullable();
            $t->text('source_path');
            $t->string('steps_hash')->nullable();
            $t->json('capabilities')->nullable();
            $t->json('requires_secrets')->nullable();
            $t->boolean('reserved')->default(false);
            $t->timestampsTz();
        });
    }
    public function down(): void {
        Schema::dropIfExists('command_registry');
    }
};
