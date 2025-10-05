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
        Schema::table('providers', function (Blueprint $table) {
            $table->string('name')->nullable()->after('provider');
            $table->text('description')->nullable()->after('name');
            $table->string('logo_url')->nullable()->after('description');
            $table->json('metadata')->nullable()->after('priority');
            $table->timestamp('synced_at')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'logo_url', 'metadata', 'synced_at']);
        });
    }
};
