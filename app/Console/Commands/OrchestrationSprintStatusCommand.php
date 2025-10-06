<?php

namespace App\Console\Commands;

use App\Services\SprintOrchestrationService;
use Illuminate\Console\Command;

class OrchestrationSprintStatusCommand extends Command
{
    protected $signature = 'orchestration:sprint:status
        {sprint : Sprint code or UUID}
        {status : Status label to set}
        {--note= : Optional note appended to sprint notes}
        {--json : Output JSON instead of tables}';

    protected $description = 'Update a sprint status label and optionally append a note.';

    public function handle(SprintOrchestrationService $service): int
    {
        $sprint = $this->argument('sprint');
        $status = $this->argument('status');
        $note = $this->option('note');

        $updated = $service->updateStatus($sprint, $status, $note);
        $detail = $service->detail($updated, ['include_tasks' => false]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('Sprint %s set to %s', $detail['sprint']['code'], $detail['sprint']['status'] ?? 'n/a'));

        if ($note) {
            $this->line('Note: '.$note);
        }

        return self::SUCCESS;
    }
}
