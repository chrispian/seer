<?php

namespace App\Console\Commands;

use App\Models\OrchestrationEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ArchiveOrchestrationEvents extends Command
{
    protected $signature = 'orchestration:archive-events 
                            {--older-than=90 : Archive events older than N days}
                            {--priority=P2 : Minimum priority to keep indefinitely (P0, P1, P2, P3)}
                            {--dry-run : Show what would be archived without actually archiving}';

    protected $description = 'Archive old orchestration events based on retention policies';

    public function handle(): int
    {
        $olderThan = (int) $this->option('older-than');
        $priority = $this->option('priority');
        $dryRun = $this->option('dry-run');

        $cutoffDate = Carbon::now()->subDays($olderThan);

        $this->info("Archiving events older than {$cutoffDate->toDateString()}...");

        $query = OrchestrationEvent::query()
            ->whereNull('archived_at')
            ->where('emitted_at', '<', $cutoffDate);

        if ($priority) {
            $highPriorityEventTypes = $this->getHighPriorityEventTypes($priority);
            $query->whereNotIn('event_type', $highPriorityEventTypes);
        }

        $count = $query->count();

        if ($dryRun) {
            $this->warn("[DRY RUN] Would archive {$count} events");
            
            $events = $query->limit(10)->get();
            $this->table(
                ['ID', 'Event Type', 'Entity', 'Emitted At'],
                $events->map(fn($e) => [
                    $e->id,
                    $e->event_type,
                    "{$e->entity_type}:{$e->entity_id}",
                    $e->emitted_at->toDateTimeString(),
                ])
            );

            if ($count > 10) {
                $this->info("... and " . ($count - 10) . " more events");
            }

            return 0;
        }

        $query->update(['archived_at' => now()]);

        $this->info("✓ Archived {$count} events");

        return 0;
    }

    private function getHighPriorityEventTypes(string $minPriority): array
    {
        $priorityMap = [
            'P0' => ['orchestration.task.blocked', 'orchestration.task.priority_changed'],
            'P1' => ['orchestration.sprint.status_changed', 'orchestration.task.status_updated'],
            'P2' => ['orchestration.sprint.created', 'orchestration.task.created'],
            'P3' => [],
        ];

        $priorities = array_keys($priorityMap);
        $minIndex = array_search($minPriority, $priorities);

        $eventTypes = [];
        for ($i = 0; $i <= $minIndex; $i++) {
            $eventTypes = array_merge($eventTypes, $priorityMap[$priorities[$i]]);
        }

        return $eventTypes;
    }
}
