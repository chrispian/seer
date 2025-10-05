<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->createSqliteVectorSupport();
        } elseif ($driver === 'pgsql') {
            $this->ensurePostgresVectorSupport();
        }
    }

    protected function createSqliteVectorSupport(): void
    {
        // Check if fragment_embeddings table exists, if not create it
        if (! Schema::hasTable('fragment_embeddings')) {
            Schema::create('fragment_embeddings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fragment_id')->constrained()->cascadeOnDelete();
                $table->string('provider');
                $table->string('model');
                $table->unsignedInteger('dims');
                $table->binary('embedding'); // BLOB for SQLite vector storage
                $table->string('content_hash');
                $table->timestamps();

                $table->unique(['fragment_id', 'provider', 'model', 'content_hash']);
            });
        } else {
            // Add missing columns if table exists
            Schema::table('fragment_embeddings', function (Blueprint $table) {
                if (! Schema::hasColumn('fragment_embeddings', 'model')) {
                    $table->string('model')->after('provider');
                }
                if (! Schema::hasColumn('fragment_embeddings', 'content_hash')) {
                    $table->string('content_hash')->after('embedding');
                }
                if (! Schema::hasColumn('fragment_embeddings', 'embedding')) {
                    $table->binary('embedding')->after('dims');
                }
            });
        }

        // Try to create FTS5 table for text search if supported
        try {
            DB::statement("
                CREATE VIRTUAL TABLE IF NOT EXISTS fragments_fts 
                USING fts5(title, message, content='fragments', content_rowid='id')
            ");

            // Populate FTS table if it's empty
            $ftsCount = DB::select('SELECT COUNT(*) as count FROM fragments_fts')[0]->count;
            if ($ftsCount == 0) {
                DB::statement('
                    INSERT INTO fragments_fts(rowid, title, message) 
                    SELECT id, title, message FROM fragments
                ');
            }
        } catch (\Exception $e) {
            // FTS5 might not be available, that's OK
        }
    }

    protected function ensurePostgresVectorSupport(): void
    {
        // Add missing columns to existing PostgreSQL table if needed
        if (Schema::hasTable('fragment_embeddings')) {
            Schema::table('fragment_embeddings', function (Blueprint $table) {
                if (! Schema::hasColumn('fragment_embeddings', 'model')) {
                    $table->string('model')->after('provider');
                }
                if (! Schema::hasColumn('fragment_embeddings', 'content_hash')) {
                    $table->string('content_hash')->after('embedding');
                }
            });

            // Update unique constraint to include model and content_hash
            try {
                DB::statement('ALTER TABLE fragment_embeddings DROP CONSTRAINT IF EXISTS fragment_embeddings_fragment_id_provider_unique');
                DB::statement('ALTER TABLE fragment_embeddings ADD CONSTRAINT fragment_embeddings_unique UNIQUE (fragment_id, provider, model, content_hash)');
            } catch (\Exception $e) {
                // Constraint might already exist or table structure might be different
            }
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Drop FTS table if it exists
            try {
                DB::statement('DROP TABLE IF EXISTS fragments_fts');
            } catch (\Exception $e) {
                // Ignore if table doesn't exist
            }
        }

        // Note: We don't drop the fragment_embeddings table or remove columns
        // as this could cause data loss and the original migration handles that
    }
};
