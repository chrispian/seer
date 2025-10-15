<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fe_ui_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., ui.halloween_haunt
            $table->string('description')->nullable();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('rollout')->default(0); // 0..100
            $table->json('conditions_json')->nullable(); // { roles:[], paths:[], modules:[], users:[], percentageOverrides:[] }
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('fe_ui_feature_flags');
    }
};
