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
        Schema::rename('provider_configs', 'providers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('providers', 'provider_configs');
    }
};
