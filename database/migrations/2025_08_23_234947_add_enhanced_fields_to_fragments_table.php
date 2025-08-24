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
        Schema::table('fragments', function (Blueprint $table) {
            // Enhanced search and recall fields
            $table->char('hash', 64)->nullable()->after('message')->comment('Content hash for dedupe/idempotency');
            $table->timestamp('deleted_at')->nullable()->after('updated_at')->comment('Soft delete with provenance');
            $table->tinyInteger('importance', false, true)->nullable()->default(0)->after('hash')->comment('Quick ranking for WM/LTM (0-100)');
            $table->tinyInteger('confidence', false, true)->nullable()->default(0)->after('importance')->comment('Confidence of auto-tagging/extraction');
            $table->boolean('pinned')->default(false)->after('confidence')->comment('Guaranteed presence in briefs');
            $table->char('lang', 5)->nullable()->after('pinned')->comment('ISO language for tokenization/search');
            $table->bigInteger('workspace_id', false, true)->nullable()->after('category_id')->comment('Project/workspace facet');
            $table->string('mime', 64)->nullable()->after('lang')->comment('Fast content type filtering');
            $table->smallInteger('object_type_id', false, true)->nullable()->after('mime')->comment('FK to object_types for typed objects');
            $table->smallInteger('object_version', false, true)->nullable()->after('object_type_id')->comment('Object type version for migrations');
            
            // Convert existing state from string to JSON for richer state management
            $table->json('state_json')->nullable()->after('state')->comment('Rich state data as JSON');
            
            // Add indexes for performance
            $table->index('hash');
            $table->index('importance');
            $table->index('pinned');
            $table->index('workspace_id');
            $table->index('mime');
            $table->index('object_type_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragments', function (Blueprint $table) {
            $table->dropIndex(['hash']);
            $table->dropIndex(['importance']);
            $table->dropIndex(['pinned']);
            $table->dropIndex(['workspace_id']);
            $table->dropIndex(['mime']);
            $table->dropIndex(['object_type_id']);
            $table->dropIndex(['deleted_at']);
            
            $table->dropColumn([
                'hash',
                'deleted_at',
                'importance',
                'confidence',
                'pinned',
                'lang',
                'workspace_id',
                'mime',
                'object_type_id',
                'object_version',
                'state_json'
            ]);
        });
    }
};
