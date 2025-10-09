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
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            $table->string('detail_component')->nullable()->after('row_display_mode');
            $table->json('detail_fields')->nullable()->after('detail_component');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            $table->dropColumn(['detail_component', 'detail_fields']);
        });
    }
};
