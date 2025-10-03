<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tool_invocations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id')->nullable();
            $t->uuid('workspace_id')->nullable();
            $t->string('tool_slug');
            $t->string('command_slug')->nullable();
            $t->uuid('fragment_id')->nullable();
            $t->json('request')->nullable();
            $t->json('response')->nullable();
            $t->string('status')->default('ok'); // ok|error
            $t->float('duration_ms')->nullable();
            $t->timestampTz('created_at')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tool_invocations');
    }
};
