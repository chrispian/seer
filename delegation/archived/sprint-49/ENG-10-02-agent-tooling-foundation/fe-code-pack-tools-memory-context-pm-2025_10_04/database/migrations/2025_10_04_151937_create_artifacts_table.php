<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id')->nullable()->index();
            $table->string('type');
            $table->string('mime')->nullable();
            $table->string('path');
            $table->string('sha256', 64);
            $table->string('created_by_tool');
            $table->uuid('source_query_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps(); // timestamps last
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
