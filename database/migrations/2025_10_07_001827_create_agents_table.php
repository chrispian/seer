<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Agent name');
            $table->string('designation', 5)->unique()->comment('4-char designation like R2-D2, C3PO');
            $table->uuid('agent_profile_id')->comment('Base agent profile template');
            $table->text('persona')->nullable()->comment('Agent personality and behavior');
            $table->json('tool_config')->nullable()->comment('Tool-specific configuration and overrides');
            $table->json('metadata')->nullable()->comment('Additional agent metadata');
            $table->integer('version')->default(1)->comment('Agent configuration version');
            $table->string('status')->default('active')->comment('Status: active, inactive, archived');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agent_profile_id')
                ->references('id')
                ->on('agent_profiles')
                ->onDelete('restrict');

            $table->index(['agent_profile_id', 'status'], 'agents_profile_status_idx');
            $table->index('status', 'agents_status_idx');
            $table->index('designation', 'agents_designation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
