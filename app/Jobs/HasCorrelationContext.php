<?php

namespace App\Jobs;

use App\Services\Telemetry\CorrelationContext;

trait HasCorrelationContext
{
    public ?string $correlationId = null;

    /**
     * Capture correlation context when job is dispatched
     */
    public function withCorrelationContext(): self
    {
        $this->correlationId = CorrelationContext::get();

        return $this;
    }

    /**
     * Restore correlation context when job is processed
     */
    protected function restoreCorrelationContext(): void
    {
        if ($this->correlationId) {
            CorrelationContext::set($this->correlationId);
        }
    }

    /**
     * Get correlation context for logging within the job
     */
    protected function getJobContext(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'job_class' => static::class,
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'queue' => $this->queue ?? 'default',
        ];
    }
}
