<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generate short codes for existing chat sessions
        $chatSessions = \App\Models\ChatSession::whereNull('short_code')
            ->orderBy('id')
            ->get();
        
        $counter = 1;
        foreach ($chatSessions as $session) {
            $shortCode = 'c' . $counter;
            $session->update(['short_code' => $shortCode]);
            $counter++;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all short codes
        \App\Models\ChatSession::query()->update(['short_code' => null]);
    }
};
