<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->index();
            $table->string('name');
            $table->string('version')->default('1.0');
            $table->enum('source', ['builtin', 'mcp'])->default('builtin')->index();
            $table->string('mcp_server')->nullable();
            
            $table->text('summary');
            $table->text('selection_hint')->nullable();
            $table->text('syntax')->nullable();
            
            $table->json('args_schema')->nullable();
            $table->json('examples')->nullable();
            $table->json('weights')->nullable();
            $table->json('permissions')->nullable();
            $table->json('constraints')->nullable();
            $table->json('metadata')->nullable();
            
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('overridden')->default(false);
            
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['source', 'enabled']);
            $table->index(['mcp_server', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_definitions');
    }
};
