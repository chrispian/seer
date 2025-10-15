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
        Schema::table('orchestration_sprints', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->after('owner');
            $table->date('ends_on')->nullable()->after('starts_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orchestration_sprints', function (Blueprint $table) {
            $table->dropColumn(['starts_on', 'ends_on']);
        });
    }
};
