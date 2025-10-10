<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Sprint\UpdateStatusCommand;
use Illuminate\Console\Command;

class OrchestrationSprintStatusCommand extends Command
{
    protected $signature = 'orchestration:sprint:status
        {sprint : Sprint code or UUID}
        {status : Status label to set}
        {--note= : Optional note appended to sprint notes}
        {--json : Output JSON instead of tables}';

    protected $description = 'Update a sprint status label and optionally append a note.';

    public function handle(): int
    {
        $command = new UpdateStatusCommand([
            'code' => $this->argument('sprint'),
            'status' => $this->argument('status'),
            'note' => $this->option('note'),
        ]);

        $command->setContext('cli');
        $result = $command->handle();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $data = $result['data'];
        $this->info(sprintf('Sprint %s set to %s', $data['code'], $data['status'] ?? 'n/a'));

        if ($this->option('note')) {
            $this->line('Note: '.$this->option('note'));
        }

        return self::SUCCESS;
    }
}
