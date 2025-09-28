<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes for common widget queries on PostgreSQL
        DB::statement("CREATE INDEX IF NOT EXISTS fragments_turn_created_idx ON fragments USING btree ((metadata->>'turn'), created_at)");
        DB::statement("CREATE INDEX IF NOT EXISTS fragments_session_created_idx ON fragments USING btree ((metadata->>'session_id'), created_at)");
        DB::statement("CREATE INDEX IF NOT EXISTS fragments_provider_created_idx ON fragments USING btree ((metadata->>'provider'), created_at)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS fragments_turn_created_idx");
        DB::statement("DROP INDEX IF EXISTS fragments_session_created_idx");
        DB::statement("DROP INDEX IF EXISTS fragments_provider_created_idx");
    }
};
