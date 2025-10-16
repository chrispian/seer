<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_actions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type');
            $table->string('handler')->nullable();
            $table->json('payload_schema_json')->nullable();
            $table->json('policy_json')->nullable();
            $table->timestamps();
            
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_actions');
    }
};
