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
        Schema::table('fe_ui_actions', function (Blueprint $table) {
            $table->json('payload_schema_json')->nullable()->comment('params shape/validation');
            $table->json('policy_json')->nullable()->comment('who can trigger (roles/permissions)');
        });
    }

    public function down(): void
    {
        Schema::table('fe_ui_actions', function (Blueprint $table) {
            $table->dropColumn(['payload_schema_json', 'policy_json']);
        });
    }
};
