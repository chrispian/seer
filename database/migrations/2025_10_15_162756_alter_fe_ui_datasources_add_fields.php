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
        Schema::table('fe_ui_datasources', function (Blueprint $table) {
            $table->json('default_params_json')->nullable()->comment('default query parameters');
            $table->json('capabilities_json')->nullable()->comment('{"supports": ["list","detail","search","paginate","aggregate"]}');
            $table->json('schema_json')->nullable()->comment('shape of data, meta, filters, sorts');
        });
    }

    public function down(): void
    {
        Schema::table('fe_ui_datasources', function (Blueprint $table) {
            $table->dropColumn(['default_params_json', 'capabilities_json', 'schema_json']);
        });
    }
};
