<?php

use App\Database\MigrationHelpers\VectorMigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only create FTS5 support for SQLite
        if (! VectorMigrationHelper::isSQLite()) {
            return;
        }

        if (! VectorMigrationHelper::hasFTS5Support()) {
            \Illuminate\Support\Facades\Log::info('FTS5 not available, skipping text search index creation');

            return;
        }

        $this->createFtsTable();
        $this->populateFtsTable();
        $this->createFtsTriggers();
    }

    protected function createFtsTable(): void
    {
        try {
            // Create FTS5 virtual table for full-text search
            DB::statement('
                CREATE VIRTUAL TABLE IF NOT EXISTS fragments_fts USING fts5(
                    title,
                    content,
                    content=fragments,
                    content_rowid=id
                )
            ');

            \Illuminate\Support\Facades\Log::info('FTS5 table created successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('FTS5 table creation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function populateFtsTable(): void
    {
        try {
            // Check if FTS table is empty before populating
            $count = DB::select('SELECT COUNT(*) as count FROM fragments_fts')[0]->count ?? 0;

            if ($count == 0) {
                // Populate FTS table with existing fragments
                DB::statement("
                    INSERT INTO fragments_fts(rowid, title, content)
                    SELECT 
                        id,
                        COALESCE(title, '') as title,
                        COALESCE(edited_message, message, '') as content
                    FROM fragments
                    WHERE COALESCE(edited_message, message, '') != ''
                ");

                $newCount = DB::select('SELECT COUNT(*) as count FROM fragments_fts')[0]->count ?? 0;
                \Illuminate\Support\Facades\Log::info('FTS5 table populated', ['fragments_indexed' => $newCount]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('FTS5 table population failed', [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - population failure shouldn't break migration
        }
    }

    protected function createFtsTriggers(): void
    {
        try {
            // Drop existing triggers first
            DB::statement('DROP TRIGGER IF EXISTS fragments_fts_insert');
            DB::statement('DROP TRIGGER IF EXISTS fragments_fts_update');
            DB::statement('DROP TRIGGER IF EXISTS fragments_fts_delete');

            // Trigger for INSERT
            DB::statement('
                CREATE TRIGGER fragments_fts_insert AFTER INSERT ON fragments
                BEGIN
                    INSERT INTO fragments_fts(rowid, title, content)
                    VALUES (NEW.id, COALESCE(NEW.title, ""), COALESCE(NEW.edited_message, NEW.message, ""));
                END
            ');

            // Trigger for UPDATE
            DB::statement('
                CREATE TRIGGER fragments_fts_update AFTER UPDATE ON fragments
                WHEN OLD.title IS NOT NEW.title OR OLD.message IS NOT NEW.message OR OLD.edited_message IS NOT NEW.edited_message
                BEGIN
                    UPDATE fragments_fts SET 
                        title = COALESCE(NEW.title, ""),
                        content = COALESCE(NEW.edited_message, NEW.message, "")
                    WHERE rowid = NEW.id;
                END
            ');

            // Trigger for DELETE
            DB::statement('
                CREATE TRIGGER fragments_fts_delete AFTER DELETE ON fragments
                BEGIN
                    DELETE FROM fragments_fts WHERE rowid = OLD.id;
                END
            ');

            \Illuminate\Support\Facades\Log::info('FTS5 triggers created successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('FTS5 trigger creation failed', [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - trigger failure shouldn't break migration
        }
    }

    public function down(): void
    {
        if (! VectorMigrationHelper::isSQLite()) {
            return;
        }

        try {
            // Drop triggers
            DB::statement('DROP TRIGGER IF EXISTS fragments_fts_insert');
            DB::statement('DROP TRIGGER IF EXISTS fragments_fts_update');
            DB::statement('DROP TRIGGER IF EXISTS fragments_fts_delete');

            // Drop FTS table
            DB::statement('DROP TABLE IF EXISTS fragments_fts');

            \Illuminate\Support\Facades\Log::info('FTS5 support removed');
        } catch (\Exception $e) {
            // Ignore errors during rollback
            \Illuminate\Support\Facades\Log::warning('FTS5 cleanup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
};
