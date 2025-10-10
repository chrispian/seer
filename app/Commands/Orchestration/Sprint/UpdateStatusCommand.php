<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Services\SprintOrchestrationService;

class UpdateStatusCommand extends BaseCommand
{
    protected string $code;
    protected string $status;
    protected ?string $note = null;

    public function __construct(array $options = [])
    {
        $this->code = $options['code'] ?? throw new \InvalidArgumentException('Sprint code is required');
        $this->status = $options['status'] ?? throw new \InvalidArgumentException('Status is required');
        $this->note = $options['note'] ?? null;
    }

    public function handle(): array
    {
        $service = app(SprintOrchestrationService::class);

        $sprint = $service->updateStatus($this->code, $this->status, $this->note);

        $detail = $service->detail($sprint, [
            'include_tasks' => false,
        ]);

        $data = $detail['sprint'];

        return $this->respond($data, $this->context === 'web' ? 'SprintDetailModal' : null);
    }

    protected function getType(): string
    {
        return 'sprint';
    }

    public static function getName(): string
    {
        return 'Sprint Update Status';
    }

    public static function getDescription(): string
    {
        return 'Update sprint status and optionally append a note';
    }

    public static function getUsage(): string
    {
        return '/sprint-status code status [note]';
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
                'description' => 'Sprint code or UUID',
                'required' => true,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Status label (e.g., "Planned", "In Progress", "Completed")',
                'required' => true,
            ],
            'note' => [
                'type' => 'string',
                'description' => 'Optional note to append to sprint notes',
                'required' => false,
            ],
        ];
    }
}
