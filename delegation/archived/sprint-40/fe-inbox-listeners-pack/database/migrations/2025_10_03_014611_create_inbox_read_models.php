<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_metrics_daily', function (Blueprint $t) {
            $t->date('day');
            $t->integer('accepted_count')->default(0);
            $t->integer('archived_count')->default(0);
            $t->bigInteger('review_time_ms_sum')->default(0);
            $t->integer('review_time_ms_count')->default(0);
            $t->primary(['day']);
        });

        Schema::create('fragment_activity', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('fragment_id');
            $t->string('action'); // accepted|archived|reopened|edited
            $t->uuid('by_user')->nullable();
            $t->json('payload')->nullable();
            $t->timestampTz('ts')->useCurrent();
            $t->index(['fragment_id', 'ts']);
            $t->index(['action', 'ts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fragment_activity');
        Schema::dropIfExists('inbox_metrics_daily');
    }
};
