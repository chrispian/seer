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
            // Add NOT NULL constraint to message column
            $table->text('message')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            // Remove NOT NULL constraint (make nullable again)
            $table->text('message')->nullable()->change();
        });
    }
};
