<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main telemetry events table
        Schema::create('telemetry_events', function (Blueprint $table) {
            $table->id();
            $table->string('correlation_id')->index();
            $table->string('event_type', 50)->index(); // tool, command, fragment, chat
            $table->string('event_name', 100)->index(); // start, complete, error, etc.
            $table->timestamp('timestamp')->index();
            $table->string('component', 100)->index(); // specific component name
            $table->string('operation', 100)->nullable()->index(); // specific operation
            $table->json('metadata')->nullable(); // structured event data
            $table->json('context')->nullable(); // correlation context, user, request info
            $table->json('performance')->nullable(); // timing, memory, etc.
            $table->text('message')->nullable(); // human readable description
            $table->string('level', 20)->default('info')->index(); // debug, info, warning, error, critical
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['event_type', 'timestamp']);
            $table->index(['correlation_id', 'timestamp']);
            $table->index(['component', 'timestamp']);
            $table->index(['level', 'timestamp']);
        });

        // Telemetry metrics table for aggregated data
        Schema::create('telemetry_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name', 100)->index();
            $table->string('component', 100)->index();
            $table->string('metric_type', 20)->index(); // counter, gauge, histogram
            $table->decimal('value', 20, 6);
            $table->json('labels')->nullable(); // key-value pairs for grouping
            $table->timestamp('timestamp')->index();
            $table->string('aggregation_period', 20)->index(); // raw, 1m, 5m, 1h, 1d
            $table->timestamps();

            // Composite indexes for efficient metric queries
            $table->index(['metric_name', 'timestamp']);
            $table->index(['component', 'metric_name', 'timestamp']);
            $table->index(['aggregation_period', 'timestamp']);
        });

        // Telemetry alerts table for tracking alert conditions
        Schema::create('telemetry_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_name', 100)->index();
            $table->string('component', 100)->index();
            $table->string('condition_type', 50); // threshold, pattern, anomaly
            $table->json('condition_config'); // threshold values, patterns, etc.
            $table->string('status', 20)->default('active')->index(); // active, firing, resolved, disabled
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->json('notification_config')->nullable(); // channels, recipients, etc.
            $table->timestamps();

            $table->index(['status', 'component']);
        });

        // Telemetry correlation chains table
        Schema::create('telemetry_correlation_chains', function (Blueprint $table) {
            $table->id();
            $table->string('chain_id')->unique();
            $table->string('root_correlation_id')->index();
            $table->integer('depth')->index();
            $table->timestamp('started_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_events')->default(0);
            $table->json('chain_metadata')->nullable(); // summary info about the chain
            $table->string('status', 20)->default('active')->index(); // active, completed, failed, timeout
            $table->timestamps();

            $table->index(['started_at', 'status']);
        });

        // Health check results table
        Schema::create('telemetry_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('component', 100)->index();
            $table->string('check_name', 100)->index();
            $table->boolean('is_healthy')->index();
            $table->text('error_message')->nullable();
            $table->decimal('response_time_ms', 10, 3)->nullable();
            $table->json('check_metadata')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();

            $table->index(['component', 'checked_at']);
            $table->index(['is_healthy', 'checked_at']);
        });

        // Performance snapshots for trend analysis
        Schema::create('telemetry_performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('component', 100)->index();
            $table->string('operation', 100)->index();
            $table->decimal('duration_ms', 10, 3);
            $table->bigInteger('memory_usage_bytes')->nullable();
            $table->integer('cpu_usage_percent')->nullable();
            $table->json('resource_metrics')->nullable(); // disk I/O, network, etc.
            $table->string('performance_class', 20)->index(); // fast, normal, slow, critical
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['component', 'operation', 'recorded_at']);
            $table->index(['performance_class', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_performance_snapshots');
        Schema::dropIfExists('telemetry_health_checks');
        Schema::dropIfExists('telemetry_correlation_chains');
        Schema::dropIfExists('telemetry_alerts');
        Schema::dropIfExists('telemetry_metrics');
        Schema::dropIfExists('telemetry_events');
    }
};