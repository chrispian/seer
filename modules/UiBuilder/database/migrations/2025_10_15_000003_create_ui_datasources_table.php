<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_datasources', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('model_class')->nullable();
            $table->json('default_params_json')->nullable();
            $table->json('capabilities_json')->nullable();
            $table->json('schema_json')->nullable();
            $table->string('handler')->nullable();
            $table->timestamps();
            
            $table->index('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_datasources');
    }
};
