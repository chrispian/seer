<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('fragments', function (Blueprint $t) {
            if (!Schema::hasColumn('fragments','inbox_status')) $t->string('inbox_status')->default('pending')->index();
            if (!Schema::hasColumn('fragments','inbox_reason')) $t->text('inbox_reason')->nullable();
            if (!Schema::hasColumn('fragments','inbox_at')) $t->timestampTz('inbox_at')->nullable()->index();
            if (!Schema::hasColumn('fragments','reviewed_at')) $t->timestampTz('reviewed_at')->nullable();
            if (!Schema::hasColumn('fragments','reviewed_by')) $t->uuid('reviewed_by')->nullable()->index();
        });
        // Backfill handled in a separate command or seeder if needed.
    }
    public function down(): void {
        Schema::table('fragments', function (Blueprint $t) {
            if (Schema::hasColumn('fragments','inbox_status')) $t->dropColumn('inbox_status');
            if (Schema::hasColumn('fragments','inbox_reason')) $t->dropColumn('inbox_reason');
            if (Schema::hasColumn('fragments','inbox_at')) $t->dropColumn('inbox_at');
            if (Schema::hasColumn('fragments','reviewed_at')) $t->dropColumn('reviewed_at');
            if (Schema::hasColumn('fragments','reviewed_by')) $t->dropColumn('reviewed_by');
        });
    }
};
