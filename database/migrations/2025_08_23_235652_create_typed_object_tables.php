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
        // Todos - structured task management
        Schema::create('todos', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->string('title', 255)->nullable();
            $table->json('state')->nullable()->comment('Rich state data: due_at, priority, status, etc.');
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // Contacts - normalized contact data
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->string('full_name', 255)->nullable();
            $table->json('emails')->nullable();
            $table->json('phones')->nullable();
            $table->string('organization', 255)->nullable();
            $table->json('state')->nullable()->comment('Rich state data: roles, tags, etc.');
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // Links - URL metadata with domain indexing
        Schema::create('links', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->text('url');
            $table->text('normalized_url')->nullable();
            $table->string('domain', 255)->nullable();
            $table->string('title', 512)->nullable();
            $table->text('description')->nullable();
            $table->datetime('fetched_at')->nullable();
            $table->smallInteger('fetch_status')->nullable();
            $table->json('state')->nullable()->comment('Rich state data: read/unread, rating, etc.');
            $table->index('domain');
            $table->index(['normalized_url(255)']);
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // Files - content-addressed media with MIME handling
        Schema::create('files', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->text('uri');
            $table->enum('storage_kind', ['local', 'obsidian', 'remote', 's3', 'gdrive']);
            $table->string('mime', 128);
            $table->bigInteger('bytes', false, true)->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->json('exif')->nullable()->comment('Camera, GPS, metadata');
            $table->json('state')->nullable()->comment('Processed flags, privacy, etc.');
            $table->index('mime');
            $table->index('content_hash');
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // File text - extracted text content (OCR/PDF/MD)
        Schema::create('file_text', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->longText('content');
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // Thumbnails - generated thumbnails for media files
        Schema::create('thumbnails', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true);
            $table->enum('kind', ['small', 'medium', 'large']);
            $table->text('uri');
            $table->primary(['fragment_id', 'kind']);
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // Calendar events - temporal objects
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->string('location', 255)->nullable();
            $table->json('attendees')->nullable()->comment('Emails, names');
            $table->json('state')->nullable()->comment('RSVP, recurrence, conferencing');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
        });

        // Meetings - meeting notes/artifacts
        Schema::create('meetings', function (Blueprint $table) {
            $table->bigInteger('fragment_id', false, true)->primary();
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->json('participants')->nullable();
            $table->bigInteger('calendar_event_id', false, true)->nullable();
            $table->json('state')->nullable()->comment('Agenda, minutes, action items');
            $table->foreign('fragment_id')->references('id')->on('fragments')->cascadeOnDelete();
            $table->foreign('calendar_event_id')->references('fragment_id')->on('calendar_events')->nullOnDelete();
        });

        // Sessions - chat sessions/context
        Schema::create('sessions', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->bigInteger('user_id', false, true);
            $table->bigInteger('workspace_id', false, true)->nullable();
            $table->bigInteger('agent_id', false, true)->nullable();
            $table->string('title', 255)->nullable();
            $table->char('context_hash', 64)->nullable()->comment('Baked context contract hash');
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->json('meta')->nullable();
            $table->index(['user_id', 'started_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Vaults - storage containers
        Schema::create('vaults', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->string('key', 128)->unique();
            $table->string('label', 255);
            $table->text('root_uri')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Projects - project/workspace organization
        Schema::create('projects', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->bigInteger('workspace_id', false, true)->nullable();
            $table->string('key', 128)->unique();
            $table->string('name', 255);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('workspace_id');
        });

        // Collections - playlists/boards/bundles
        Schema::create('collections', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->bigInteger('workspace_id', false, true)->nullable();
            $table->string('title', 255);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Collection items - items in collections
        Schema::create('collection_items', function (Blueprint $table) {
            $table->bigInteger('collection_id', false, true);
            $table->integer('order_pos');
            $table->string('object_type', 32);
            $table->bigInteger('object_id', false, true);
            $table->primary(['collection_id', 'order_pos']);
            $table->index(['object_type', 'object_id']);
            $table->timestamps();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
        });

        // Reminders - simple schedulers
        Schema::create('reminders', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->bigInteger('fragment_id', false, true)->nullable();
            $table->bigInteger('user_id', false, true);
            $table->datetime('due_at');
            $table->string('message', 512)->nullable();
            $table->json('state')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->index(['user_id', 'due_at', 'completed_at']);
            $table->foreign('fragment_id')->references('id')->on('fragments')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Triggers - event-based schedulers
        Schema::create('triggers', function (Blueprint $table) {
            $table->bigInteger('id', false, true)->primary()->autoIncrement();
            $table->bigInteger('user_id', false, true);
            $table->enum('kind', ['cron', 'interval', 'event']);
            $table->string('spec', 255)->nullable()->comment('Cron string or rule key');
            $table->json('payload')->nullable()->comment('What to run');
            $table->datetime('next_run_at')->nullable();
            $table->datetime('last_run_at')->nullable();
            $table->json('state')->nullable();
            $table->index(['user_id', 'next_run_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to handle foreign key constraints
        Schema::dropIfExists('triggers');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('vaults');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('thumbnails');
        Schema::dropIfExists('file_text');
        Schema::dropIfExists('files');
        Schema::dropIfExists('links');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('todos');
    }
};
