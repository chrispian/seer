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
        Schema::table('a_i_credentials', function (Blueprint $table) {
            $table->json('ui_metadata')->nullable()->after('metadata');
            $table->unsignedBigInteger('provider_config_id')->nullable()->after('provider');
            $table->timestamp('last_used_at')->nullable()->after('is_active');
            $table->integer('usage_count')->default(0)->after('last_used_at');
            $table->decimal('total_cost', 10, 6)->default(0)->after('usage_count');

            // Enhanced indexes for performance
            $table->index(['provider', 'is_active']);
            $table->index('last_used_at');
            $table->index('provider_config_id');

            // Foreign key constraint (will be populated after provider_configs exist)
            $table->foreign('provider_config_id')->references('id')->on('provider_configs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('a_i_credentials', function (Blueprint $table) {
            $table->dropForeign(['provider_config_id']);
            $table->dropIndex(['provider', 'is_active']);
            $table->dropIndex(['last_used_at']);
            $table->dropIndex(['provider_config_id']);

            $table->dropColumn([
                'ui_metadata',
                'provider_config_id',
                'last_used_at',
                'usage_count',
                'total_cost',
            ]);
        });
    }
};
