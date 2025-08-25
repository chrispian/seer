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
        // Articles - draft assembly system
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('workspace_id', false, true)->nullable();
            $table->string('title', 255)->nullable();
            $table->enum('status', ['draft', 'review', 'published'])->default('draft');
            $table->json('meta')->nullable()->comment('Article metadata');
            $table->timestamps();
            $table->index('workspace_id');
            $table->index('status');
        });

        // Article fragments - ordered assembly of content blocks
        Schema::create('article_fragments', function (Blueprint $table) {
            $table->bigInteger('article_id', false, true);
            $table->bigInteger('fragment_id', false, true)->nullable()->comment('NULL when copy-only block');
            $table->integer('order_pos');
            $table->mediumText('body')->nullable()->comment('Snapshot for edit_mode=copy');
            $table->enum('edit_mode', ['reference', 'copy'])->default('reference');
            
            $table->primary(['article_id', 'order_pos']);
            $table->index('fragment_id');
            
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->foreign('fragment_id')->references('id')->on('fragments')->nullOnDelete();
        });

        // Builds - daily logs/automatic summaries
        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('article_id', false, true)->comment('The rendered brief/log');
            $table->bigInteger('workspace_id', false, true)->nullable();
            $table->datetime('range_start');
            $table->datetime('range_end');
            $table->string('kind', 32)->default('daily')->comment('daily, weekly, session');
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['workspace_id', 'range_start', 'kind']);
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('builds');
        Schema::dropIfExists('article_fragments');
        Schema::dropIfExists('articles');
    }
};
