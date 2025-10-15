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
        Schema::table('fe_ui_pages', function (Blueprint $table) {
            $table->string('route', 255)->nullable()->comment('optional; null for modal-only pages');
            $table->json('meta_json')->nullable()->comment('SEO, breadcrumbs, etc.');
            $table->renameColumn('config', 'layout_tree_json');
            $table->string('module_key', 100)->nullable()->comment('FK to fe_ui_modules.key');
            $table->json('guards_json')->nullable()->comment('auth/roles requirements');
            $table->boolean('enabled')->default(true);

            $table->index('route');
            $table->index('module_key');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('fe_ui_pages', function (Blueprint $table) {
            $table->dropIndex(['route']);
            $table->dropIndex(['module_key']);
            $table->dropIndex(['enabled']);
            
            $table->dropColumn(['route', 'meta_json', 'module_key', 'guards_json', 'enabled']);
            $table->renameColumn('layout_tree_json', 'config');
        });
    }
};
