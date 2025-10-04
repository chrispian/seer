<?php

namespace App\Services\Commands\DSL\Steps;

class JobDispatchStep extends Step
{
    public function getType(): string
    {
        return 'job.dispatch';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];
        
        $jobClass = $with['job'] ?? null;
        $parameters = $with['parameters'] ?? [];
        $queue = $with['queue'] ?? null;
        $delay = $with['delay'] ?? null;

        if (!$jobClass) {
            throw new \InvalidArgumentException('Job dispatch requires a job class');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_dispatch' => true,
                'job' => $jobClass,
                'parameters' => $parameters,
                'queue' => $queue,
                'delay' => $delay,
            ];
        }

        // Map job names to actual job classes
        $jobClassMap = [
            'ProcessFragmentJob' => \App\Jobs\ProcessFragmentJob::class,
            'EmbedFragment' => \App\Jobs\EmbedFragment::class,
            // Add other jobs as needed
        ];

        $actualJobClass = $jobClassMap[$jobClass] ?? $jobClass;

        if (!class_exists($actualJobClass)) {
            throw new \InvalidArgumentException("Unknown job class: {$jobClass}");
        }

        // Resolve parameters from context if they contain step references
        $resolvedParameters = [];
        foreach ($parameters as $param) {
            if (is_string($param) && preg_match('/steps\.(.+?)\.output\.(.+)/', $param, $matches)) {
                $stepId = $matches[1];
                $outputKey = $matches[2];
                if (isset($context['steps'][$stepId]['output'][$outputKey])) {
                    $resolvedParameters[] = $context['steps'][$stepId]['output'][$outputKey];
                } else {
                    $resolvedParameters[] = $param; // Fallback to original
                }
            } else {
                $resolvedParameters[] = $param;
            }
        }

        // Create job instance with resolved parameters
        $jobInstance = new $actualJobClass(...$resolvedParameters);

        // Apply queue if specified
        if ($queue) {
            $jobInstance->onQueue($queue);
        }

        // Apply delay if specified
        if ($delay) {
            if (is_numeric($delay)) {
                $jobInstance->delay($delay);
            } else {
                // Parse delay string like "5 minutes", "1 hour"
                $jobInstance->delay(now()->addSeconds($this->parseDelayString($delay)));
            }
        }

        // Dispatch the job
        dispatch($jobInstance);

        return [
            'dispatched' => true,
            'job' => $jobClass,
            'parameters' => $parameters,
            'queue' => $queue,
            'delay' => $delay,
        ];
    }

    protected function parseDelayString(string $delay): int
    {
        // Simple delay parsing - can be enhanced
        if (preg_match('/(\d+)\s*(second|minute|hour|day)s?/', $delay, $matches)) {
            $amount = (int) $matches[1];
            $unit = $matches[2];

            return match ($unit) {
                'second' => $amount,
                'minute' => $amount * 60,
                'hour' => $amount * 3600,
                'day' => $amount * 86400,
                default => $amount,
            };
        }

        return (int) $delay; // Fallback to numeric seconds
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];
        return isset($with['job']);
    }
}