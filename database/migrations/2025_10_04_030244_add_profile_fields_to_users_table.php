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
        Schema::table('users', function (Blueprint $table) {
            // Profile information
            $table->string('display_name')->nullable()->after('name');
            $table->string('avatar_path')->nullable()->after('display_name');
            $table->boolean('use_gravatar')->default(true)->after('avatar_path');

            // Settings and preferences as JSON
            $table->json('profile_settings')->nullable()->after('use_gravatar');

            // Setup completion tracking
            $table->timestamp('profile_completed_at')->nullable()->after('profile_settings');

            // Add indexes for performance
            $table->index('profile_completed_at');
            $table->index('use_gravatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['profile_completed_at']);
            $table->dropIndex(['use_gravatar']);
            $table->dropColumn([
                'display_name',
                'avatar_path',
                'use_gravatar',
                'profile_settings',
                'profile_completed_at',
            ]);
        });
    }
};
