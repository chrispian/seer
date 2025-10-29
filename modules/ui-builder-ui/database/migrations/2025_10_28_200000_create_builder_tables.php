<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_ui_builder_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('user_id')->nullable();
            $table->string('page_key')->nullable();
            $table->string('title')->nullable();
            $table->string('overlay')->nullable();
            $table->string('route')->nullable();
            $table->string('module_key')->nullable();
            $table->string('layout_type')->nullable();
            $table->string('layout_id')->nullable();
            $table->json('state_json')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
            $table->index('expires_at');
        });

        Schema::create('fe_ui_builder_page_components', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('component_id');
            $table->string('component_type');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->json('props_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->json('children_json')->nullable();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('session_id')
                ->on('fe_ui_builder_sessions')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('fe_ui_builder_page_components')
                ->onDelete('cascade');

            $table->index('session_id');
            $table->index('parent_id');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_ui_builder_page_components');
        Schema::dropIfExists('fe_ui_builder_sessions');
    }
};
