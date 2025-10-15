<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fe_ui_datasources', function (Blueprint $table) {
            $table->string('handler')->nullable()->after('model_class')->comment('Model class handler (replaces model_class)');
        });
    }

    public function down(): void
    {
        Schema::table('fe_ui_datasources', function (Blueprint $table) {
            $table->dropColumn('handler');
        });
    }
};
