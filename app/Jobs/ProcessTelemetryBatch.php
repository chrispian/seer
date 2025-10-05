<?php

namespace App\Jobs;

use App\Models\TelemetryEvent;
use App\Models\TelemetryMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTelemetryBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $dataType;

    protected array $data;

    public function __construct(string $dataType, array $data)
    {
        $this->dataType = $dataType;
        $this->data = $data;

        // Set queue based on priority
        $this->onQueue(config('telemetry.performance.queue_connection', 'default'));
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                if ($this->dataType === 'events') {
                    $this->processEventsBatch();
                } elseif ($this->dataType === 'metrics') {
                    $this->processMetricsBatch();
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to process telemetry batch', [
                'type' => $this->dataType,
                'count' => count($this->data),
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    protected function processEventsBatch(): void
    {
        $batchSize = config('telemetry.storage.batch_size', 100);
        $chunks = array_chunk($this->data, $batchSize);

        foreach ($chunks as $chunk) {
            TelemetryEvent::insert($chunk);
        }

        Log::debug('Processed telemetry events batch', [
            'count' => count($this->data),
            'chunks' => count($chunks),
        ]);
    }

    protected function processMetricsBatch(): void
    {
        $batchSize = config('telemetry.storage.batch_size', 100);
        $chunks = array_chunk($this->data, $batchSize);

        foreach ($chunks as $chunk) {
            TelemetryMetric::insert($chunk);
        }

        Log::debug('Processed telemetry metrics batch', [
            'count' => count($this->data),
            'chunks' => count($chunks),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Telemetry batch processing failed permanently', [
            'type' => $this->dataType,
            'count' => count($this->data),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
