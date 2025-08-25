<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->char('input_hash', 64)->nullable()->after('message');
            $table->integer('hash_bucket')->nullable()->after('input_hash');
            
            // Add index for fast lookups on hash + bucket combination
            $table->index(['input_hash', 'hash_bucket'], 'idx_fragments_hash_bucket');
        });
    }

    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropIndex('idx_fragments_hash_bucket');
            $table->dropColumn(['input_hash', 'hash_bucket']);
        });
    }
};