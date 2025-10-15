<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fe_ui_registry', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., component.table.agent.standard
            $table->string('type'); // component|page|module|theme
            $table->string('resource_key')->nullable(); // cross-ref to fe_ui_* key
            $table->string('version')->default('0.1.0');
            $table->string('hash', 128)->nullable();
            $table->json('manifest_json')->nullable(); // files, install steps, dependencies
            $table->json('tags_json')->nullable();
            $table->enum('visibility', ['public','private'])->default('private');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['type']);
            $table->index(['visibility']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('fe_ui_registry');
    }
};
