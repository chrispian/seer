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
            $table->string('container_component')->default('DataManagementModal')->after('behaviors');
            $table->string('row_display_mode')->default('list')->after('container_component'); // list, grid, card
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            $table->dropColumn(['container_component', 'row_display_mode']);
        });
    }
};
