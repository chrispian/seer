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
        Schema::table('fe_ui_components', function (Blueprint $table) {
            $table->string('variant', 50)->nullable()->comment('e.g., standard, dense, modal, drawer');
            $table->json('schema_json')->nullable()->comment('props/slots contract');
            $table->json('defaults_json')->nullable()->comment('sane default values');
            $table->json('capabilities_json')->nullable()->comment('searchable/sortable/filterable flags');
        });
    }

    public function down(): void
    {
        Schema::table('fe_ui_components', function (Blueprint $table) {
            $table->dropColumn(['variant', 'schema_json', 'defaults_json', 'capabilities_json']);
        });
    }
};
