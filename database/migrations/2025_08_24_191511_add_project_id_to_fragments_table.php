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
        $usingSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('fragments', function (Blueprint $table) use ($usingSqlite) {
            if ($usingSqlite) {
                $table->unsignedBigInteger('project_id')->nullable()->after('vault');
            } else {
                $table->foreignId('project_id')->nullable()->after('vault')->constrained()->nullOnDelete();
            }

            $table->index(['vault', 'project_id']);
            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $usingSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('fragments', function (Blueprint $table) use ($usingSqlite) {
            if (! $usingSqlite) {
                $table->dropForeign(['project_id']);
            }

            $table->dropIndex(['vault', 'project_id']);
            $table->dropIndex(['project_id', 'created_at']);
            $table->dropColumn('project_id');
        });
    }
};
