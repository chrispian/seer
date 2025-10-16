<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('layout_tree_json');
            $table->string('route', 255)->nullable()->index();
            $table->json('meta_json')->nullable();
            $table->string('module_key', 100)->nullable()->index();
            $table->json('guards_json')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->string('hash', 64)->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_pages');
    }
};
