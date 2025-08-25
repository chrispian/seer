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
        Schema::table('fragments', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->after('message');
            $table->json('parsed_entities')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropColumn(['title', 'parsed_entities']);
        });
    }
};
