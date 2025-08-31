<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Add nullable columns first (avoid NOT NULL on existing rows)
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            if (!Schema::hasColumn('fragment_embeddings', 'model')) {
                $table->string('model')->nullable()->after('provider');
            }
            if (!Schema::hasColumn('fragment_embeddings', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->after('dims');
            }
        });

        // 2) Backfill `model`
        DB::statement("
            UPDATE fragment_embeddings e
            SET model = COALESCE(
                e.model,
                CASE
                    WHEN e.provider = 'openai' THEN 'text-embedding-3-small'
                    WHEN e.provider = 'ollama' THEN 'nomic-embed-text'
                    ELSE 'unknown'
                END
            )
            WHERE e.model IS NULL
        ");

        // 3) Backfill `content_hash` (use edited_message only if the column exists)
        $version = (string) config('fragments.embeddings.version', '1');
        $textExpr = Schema::hasColumn('fragments', 'edited_message')
            ? "COALESCE(f.edited_message, f.message, '')"
            : "COALESCE(f.message, '')";

        DB::statement("
            UPDATE fragment_embeddings e
            SET content_hash = md5(
                {$textExpr} || '|' || e.provider || '|' || COALESCE(e.model,'unknown') || '|{$version}'
            )
            FROM fragments f
            WHERE f.id = e.fragment_id
              AND e.content_hash IS NULL
        ");

        // Safety: if any rows still NULL (shouldn’t happen), give them a minimal hash
        DB::statement("
            UPDATE fragment_embeddings
            SET content_hash = md5(provider || '|' || COALESCE(model,'unknown') || '|{$version}')
            WHERE content_hash IS NULL
        ");

        // 4) Enforce NOT NULL
        DB::statement("ALTER TABLE fragment_embeddings ALTER COLUMN model SET NOT NULL");
        DB::statement("ALTER TABLE fragment_embeddings ALTER COLUMN content_hash SET NOT NULL");

        // 5) Uniqueness for idempotence
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS fragment_embeddings_unique
            ON fragment_embeddings (fragment_id, provider, model, content_hash)
        ");
    }

    public function down(): void
    {
        // Drop unique + columns (reverse)
        DB::statement("DROP INDEX IF EXISTS fragment_embeddings_unique");
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            if (Schema::hasColumn('fragment_embeddings', 'content_hash')) $table->dropColumn('content_hash');
            if (Schema::hasColumn('fragment_embeddings', 'model'))        $table->dropColumn('model');
        });
    }
};
