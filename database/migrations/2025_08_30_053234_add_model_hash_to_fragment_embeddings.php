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
            if (! Schema::hasColumn('fragment_embeddings', 'model')) {
                $table->string('model')->nullable()->after('provider');
            }
            if (! Schema::hasColumn('fragment_embeddings', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->after('dims');
            }
        });

        $this->backfillUsingQueryBuilder();

        if ($this->isSqlite()) {
            return;
        }

        // Enforce NOT NULL & unique index on databases that support it (Postgres)
        DB::statement("ALTER TABLE fragment_embeddings ALTER COLUMN model SET NOT NULL");
        DB::statement("ALTER TABLE fragment_embeddings ALTER COLUMN content_hash SET NOT NULL");

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS fragment_embeddings_unique
            ON fragment_embeddings (fragment_id, provider, model, content_hash)
        ");
    }

    public function down(): void
    {
        if (! $this->isSqlite()) {
            DB::statement("DROP INDEX IF EXISTS fragment_embeddings_unique");
        }
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            if (Schema::hasColumn('fragment_embeddings', 'content_hash')) $table->dropColumn('content_hash');
            if (Schema::hasColumn('fragment_embeddings', 'model'))        $table->dropColumn('model');
        });
    }

    private function isSqlite(): bool
    {
        return Schema::getConnection()->getDriverName() === 'sqlite';
    }

    private function backfillUsingQueryBuilder(): void
    {
        $version = (string) config('fragments.embeddings.version', '1');

        $embeddings = DB::table('fragment_embeddings')->get();

        foreach ($embeddings as $embedding) {
            $fallbackModel = match ($embedding->provider) {
                'openai' => 'text-embedding-3-small',
                'ollama' => 'nomic-embed-text',
                default => 'unknown',
            };

            $model = $embedding->model ?? $fallbackModel;

            $fragment = DB::table('fragments')
                ->select('message', Schema::hasColumn('fragments', 'edited_message') ? 'edited_message' : 'message')
                ->find($embedding->fragment_id);

            $content = '';
            if ($fragment) {
                $message = $fragment->message ?? '';
                $edited = $fragment->edited_message ?? null;
                $content = $edited !== null ? $edited : $message;
            }

            $hash = md5($content.'|'.$embedding->provider.'|'.$model.'|'.$version);

            DB::table('fragment_embeddings')
                ->where('id', $embedding->id)
                ->update([
                    'model' => $model,
                    'content_hash' => $embedding->content_hash ?? $hash,
                ]);
        }
    }
};
