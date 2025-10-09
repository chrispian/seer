<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sources')) {
            return;
        }

        $now = now();

        DB::table('sources')->updateOrInsert(
            ['key' => 'readwise'],
            [
                'label' => 'Readwise',
                'meta' => json_encode([], JSON_THROW_ON_ERROR),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('sources')) {
            return;
        }

        DB::table('sources')->where('key', 'readwise')->delete();
    }
};
