<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fe_ui_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('e.g., crm, ttrpg.characters');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('manifest_json')->comment('declares pages, required datasources, default actions');
            $table->string('version', 20)->comment('semver');
            $table->string('hash', 64);
            $table->boolean('enabled')->default(true);
            $table->integer('order')->default(0);
            $table->json('capabilities')->nullable()->comment('["search","filter","export"]');
            $table->json('permissions')->nullable()->comment('role/permission requirements');
            $table->timestamps();

            $table->index('key');
            $table->index('enabled');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_ui_modules');
    }
};
