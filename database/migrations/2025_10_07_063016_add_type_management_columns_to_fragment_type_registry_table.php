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
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('slug');
            $table->string('plural_name')->nullable()->after('display_name');
            $table->text('description')->nullable()->after('plural_name');
            $table->string('icon')->nullable()->after('description');
            $table->string('color')->nullable()->after('icon');
            
            $table->boolean('is_enabled')->default(true)->after('color');
            $table->boolean('is_system')->default(false)->after('is_enabled');
            $table->boolean('hide_from_admin')->default(false)->after('is_system');
            
            $table->json('list_columns')->nullable()->after('capabilities');
            $table->json('filters')->nullable()->after('list_columns');
            $table->json('actions')->nullable()->after('filters');
            $table->json('default_sort')->nullable()->after('actions');
            $table->integer('pagination_default')->default(50)->after('default_sort');
            
            $table->string('config_class')->nullable()->after('pagination_default');
            $table->json('behaviors')->nullable()->after('config_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'plural_name',
                'description',
                'icon',
                'color',
                'is_enabled',
                'is_system',
                'hide_from_admin',
                'list_columns',
                'filters',
                'actions',
                'default_sort',
                'pagination_default',
                'config_class',
                'behaviors',
            ]);
        });
    }
};
