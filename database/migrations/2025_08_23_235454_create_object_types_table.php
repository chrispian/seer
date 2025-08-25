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
        Schema::create('object_types', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('key', 64)->unique()->comment('Object type key: todo, contact, event, etc.');
            $table->smallInteger('version', false, true)->default(1)->comment('Schema version for migrations');
            $table->timestamps();
        });

        // Add foreign key constraint from fragments to object_types
        Schema::table('fragments', function (Blueprint $table) {
            $table->foreign('object_type_id')->references('id')->on('object_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropForeign(['object_type_id']);
        });
        
        Schema::dropIfExists('object_types');
    }
};
