<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_ui_datasources', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('model_class');
            $table->string('resolver_class');
            $table->json('capabilities');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_ui_datasources');
    }
};
