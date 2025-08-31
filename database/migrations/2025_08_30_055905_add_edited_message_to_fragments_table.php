<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            // In Postgres this maps to TEXT; nullable so existing rows don’t fail
            $table->longText('edited_message')->nullable()->after('message');
        });

        // Update your full-text index to prefer edited_message when present
        DB::statement("DROP INDEX IF EXISTS idx_frag_fulltext;");
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_frag_fulltext
            ON fragments USING GIN (
              to_tsvector(
                'simple',
                coalesce(title,'') || ' ' || coalesce(edited_message, message, '')
              )
            )
        ");
    }

    public function down(): void
    {
        // Drop index first, then column
        DB::statement("DROP INDEX IF EXISTS idx_frag_fulltext;");
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropColumn('edited_message');
        });
        // Optional: recreate old index that used only message
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_frag_fulltext
            ON fragments USING GIN (
              to_tsvector('simple', coalesce(title,'') || ' ' || coalesce(message,''))
            )
        ");
    }
};

