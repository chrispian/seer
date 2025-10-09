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

        foreach ([
            'chatgpt-web' => 'ChatGPT Web',
            'chatgpt-user' => 'ChatGPT User',
        ] as $key => $label) {
            DB::table('sources')->updateOrInsert(
                ['key' => $key],
                [
                    'label' => $label,
                    'meta' => json_encode([], JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sources')) {
            return;
        }

        DB::table('sources')->whereIn('key', ['chatgpt-web', 'chatgpt-user'])->delete();
    }
};
