<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentation', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title');
            $table->text('content');
            $table->text('excerpt')->nullable();

            $table->string('file_path', 500)->unique();
            $table->string('namespace', 100)->nullable();
            $table->string('file_hash', 64)->nullable();

            $table->string('subsystem', 100)->nullable();
            $table->string('purpose', 50)->nullable();
            $table->jsonb('tags')->default('[]');

            $table->jsonb('related_docs')->default('[]');
            $table->jsonb('related_code_paths')->default('[]');

            $table->integer('version')->default(1);
            $table->timestamp('last_modified')->nullable();
            $table->string('git_hash', 40)->nullable();

            $table->timestamps();

            $table->index('namespace');
            $table->index('subsystem');
            $table->index('purpose');
            $table->index('file_path');
        });

        DB::statement('CREATE INDEX idx_docs_tags ON documentation USING GIN(tags)');
        DB::statement("CREATE INDEX idx_docs_search ON documentation USING GIN(to_tsvector('english', title || ' ' || content))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation');
    }
};
