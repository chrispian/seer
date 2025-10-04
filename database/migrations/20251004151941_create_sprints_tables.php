<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('sprint_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sprint_id')->index();
            $table->uuid('work_item_id')->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('sprint_items');
        Schema::dropIfExists('sprints');
    }
};
