<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->string('inbox_status')->default('pending')->after('state');
            $table->text('inbox_reason')->nullable()->after('inbox_status');
            $table->timestampTz('inbox_at')->nullable()->after('inbox_reason');
            $table->timestampTz('reviewed_at')->nullable()->after('inbox_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            
            // Indexes for inbox performance
            $table->index('inbox_status');
        });
        
        // Create partial index for pending items (PostgreSQL specific)
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_fragments_inbox_pending_type_created ON fragments (type, created_at) WHERE inbox_status = \'pending\'');
        }
        
        // Backfill inbox_at for existing fragments
        DB::statement('UPDATE fragments SET inbox_at = COALESCE(inbox_at, created_at) WHERE inbox_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropIndex(['inbox_status']);
            $table->dropColumn([
                'inbox_status',
                'inbox_reason', 
                'inbox_at',
                'reviewed_at',
                'reviewed_by'
            ]);
        });
        
        // Drop partial index if it exists
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_fragments_inbox_pending_type_created');
        }
    }
};
