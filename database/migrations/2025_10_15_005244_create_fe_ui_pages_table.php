<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_ui_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('config');
            $table->string('hash', 64)->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_ui_pages');
    }
};
