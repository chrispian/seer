<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('saved_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('entity');
            $table->json('filters');
            $table->json('boosts')->nullable();
            $table->json('order_by')->nullable();
            $table->integer('limit')->nullable();
            $table->uuid('owner_id')->nullable()->index();
            $table->string('visibility')->default('private'); // private|team|public
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('saved_queries'); }
};
