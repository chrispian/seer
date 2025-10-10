<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Services\SprintOrchestrationService;

class SaveCommand extends BaseCommand
{
    protected string $code;
    protected ?string $title = null;
    protected ?string $priority = null;
    protected ?string $status = null;
    protected ?string $estimate = null;
    protected ?string $startsOn = null;
    protected ?string $endsOn = null;
    protected ?array $notes = null;
    protected ?array $meta = null;
    protected bool $upsert = true;

    public function __construct(array $options = [])
    {
        $this->code = $options['code'] ?? throw new \InvalidArgumentException('Sprint code is required');
        $this->title = $options['title'] ?? null;
        $this->priority = $options['priority'] ?? null;
        $this->status = $options['status'] ?? null;
        $this->estimate = $options['estimate'] ?? null;
        $this->startsOn = $options['starts_on'] ?? null;
        $this->endsOn = $options['ends_on'] ?? null;
        $this->notes = $options['notes'] ?? null;
        $this->meta = $options['meta'] ?? null;
        $this->upsert = $options['upsert'] ?? true;
    }

    public function handle(): array
    {
        $service = app(SprintOrchestrationService::class);

        $attributes = array_filter([
            'code' => $this->code,
            'title' => $this->title,
            'priority' => $this->priority,
            'estimate' => $this->estimate,
            'status' => $this->status,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn,
        ], static fn ($value) => $value !== null && $value !== '');

        if ($this->notes !== null && $this->notes !== []) {
            $attributes['notes'] = array_values(array_filter($this->notes));
        }

        if ($this->meta !== null && $this->meta !== []) {
            $attributes['meta'] = $this->meta;
        }

        $sprint = $service->create($attributes, $this->upsert);

        $detail = $service->detail($sprint, [
            'include_tasks' => true,
            'tasks_limit' => 10,
        ]);

        $data = $detail['sprint'];

        return $this->respond($data, $this->context === 'web' ? 'SprintDetailModal' : null);
    }


    public static function getName(): string
    {
        return 'Sprint Save';
    }

    public static function getDescription(): string
    {
        return 'Create or update a sprint with metadata, dates, and notes';
    }

    public static function getUsage(): string
    {
        return '/sprint-save code [options]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }

    public static function getInputSchema(): array
    {
        return [
            'code' => [
                'type' => 'string',
                'description' => 'Sprint code (e.g., "SPRINT-67" or just "67")',
                'required' => true,
            ],
            'title' => [
                'type' => 'string',
                'description' => 'Sprint title',
                'required' => false,
            ],
            'priority' => [
                'type' => 'string',
                'description' => 'Priority label (e.g., "High", "Medium", "Low")',
                'required' => false,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Status label (e.g., "Planned", "In Progress", "Completed")',
                'required' => false,
            ],
            'estimate' => [
                'type' => 'string',
                'description' => 'Estimate text (e.g., "2 weeks", "5 days")',
                'required' => false,
            ],
            'starts_on' => [
                'type' => 'string',
                'description' => 'Start date in Y-m-d format (e.g., "2025-10-15")',
                'required' => false,
            ],
            'ends_on' => [
                'type' => 'string',
                'description' => 'End date in Y-m-d format (e.g., "2025-10-29")',
                'required' => false,
            ],
            'notes' => [
                'type' => 'array',
                'description' => 'Array of note strings',
                'items' => ['type' => 'string'],
                'required' => false,
            ],
            'meta' => [
                'type' => 'object',
                'description' => 'Additional metadata as key-value pairs',
                'required' => false,
            ],
            'upsert' => [
                'type' => 'boolean',
                'description' => 'If true, update existing sprint; if false, fail if sprint exists',
                'default' => true,
                'required' => false,
            ],
        ];
    }
}
