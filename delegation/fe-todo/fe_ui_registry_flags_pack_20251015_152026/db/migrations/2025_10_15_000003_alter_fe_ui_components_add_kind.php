<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('fe_ui_components', function (Blueprint $table) {
            $table->enum('kind', ['primitive','composite','pattern','layout'])->default('composite')->after('type');
            $table->index(['kind']);
        });
    }
    public function down(): void {
        Schema::table('fe_ui_components', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
