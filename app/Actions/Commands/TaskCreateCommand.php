<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\WorkItem;
use Illuminate\Support\Str;

class TaskCreateCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $input = $command->input;
        
        // Parse title from input (everything before first --)
        $parts = explode('--', $input, 2);
        $title = trim($parts[0] ?? '');
        
        if (empty($title)) {
            return new CommandResponse(
                message: '❌ Task title is required. Usage: `/task-create "Task Title" --priority=medium --estimate="2 days"`',
                type: 'error',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: false
            );
        }
        
        // Parse options from remaining input
        $options = [];
        if (isset($parts[1])) {
            preg_match_all('/--(\w+)=(["\']?)([^"\'\s]+)\2/', '--' . $parts[1], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $options[$match[1]] = $match[3];
            }
        }
        
        // Set defaults
        $priority = $options['priority'] ?? 'medium';
        $estimate = $options['estimate'] ?? '2-3 days';
        $type = $options['type'] ?? 'task';
        
        // Validate priority
        if (!in_array($priority, ['low', 'medium', 'high'])) {
            return new CommandResponse(
                message: '❌ Priority must be: low, medium, or high',
                type: 'error',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: false
            );
        }
        
        // Generate estimated hours from estimate text
        $estimatedHours = $this->parseEstimateToHours($estimate);
        
        // Generate task code
        $taskCode = $this->generateTaskCode($title);
        
        // Create the task
        $task = new WorkItem();
        $task->type = $type;
        $task->status = 'backlog';
        $task->priority = $priority;
        $task->estimated_hours = $estimatedHours;
        $task->metadata = [
            'task_name' => $title,
            'task_code' => $taskCode,
            'description' => 'Created via /task-create command',
            'estimate_text' => $estimate
        ];
        $task->context_content = 'Context: Please update with background information and problem definition.';
        $task->plan_content = 'Plan: Please update with implementation strategy and approach.';
        $task->todo_content = '- [ ] Define specific requirements\n- [ ] Plan implementation approach\n- [ ] Execute development work\n- [ ] Test and validate solution';
        $task->summary_content = $title;
        $task->save();
        
        return new CommandResponse(
            message: "✅ Task created successfully!\n\n**{$title}** (`{$taskCode}`)\n- Priority: {$priority}\n- Estimate: {$estimate}\n- Status: backlog\n\nUse `/task-detail {$task->id}` to view and edit details.",
            type: 'success',
            fragments: [],
            shouldResetChat: false,
            shouldOpenPanel: false
        );
    }
    
    private function parseEstimateToHours(string $estimate): float
    {
        // Extract numbers from estimate text
        preg_match_all('/(\d+)/', $estimate, $matches);
        $numbers = array_map('intval', $matches[1]);
        
        if (empty($numbers)) {
            return 16; // Default 2 days
        }
        
        $value = $numbers[0];
        
        // Convert based on unit
        if (str_contains(strtolower($estimate), 'hour')) {
            return $value;
        } elseif (str_contains(strtolower($estimate), 'day')) {
            return $value * 8; // 8 hours per day
        } elseif (str_contains(strtolower($estimate), 'week')) {
            return $value * 40; // 40 hours per week
        }
        
        // Default to days if no unit specified
        return $value * 8;
    }
    
    private function generateTaskCode(string $title): string
    {
        // Determine prefix based on title keywords
        $titleLower = strtolower($title);
        
        if (str_contains($titleLower, 'bug') || str_contains($titleLower, 'fix') || str_contains($titleLower, 'error')) {
            $prefix = 'BUG';
        } elseif (str_contains($titleLower, 'implement') || str_contains($titleLower, 'add') || str_contains($titleLower, 'feature')) {
            $prefix = 'FEAT';
        } elseif (str_contains($titleLower, 'test') || str_contains($titleLower, 'qa')) {
            $prefix = 'TEST';
        } elseif (str_contains($titleLower, 'doc') || str_contains($titleLower, 'guide')) {
            $prefix = 'DOC';
        } elseif (str_contains($titleLower, 'upgrade') || str_contains($titleLower, 'refactor') || str_contains($titleLower, 'optimize')) {
            $prefix = 'TECH';
        } else {
            $prefix = 'TASK';
        }
        
        // Find next available number
        $existingCodes = WorkItem::whereJsonContains('metadata->task_code', $prefix)
            ->pluck('metadata')
            ->map(fn($metadata) => $metadata['task_code'] ?? null)
            ->filter()
            ->toArray();
        
        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            if (preg_match("/^{$prefix}-(\d+)$/", $code, $matches)) {
                $maxNumber = max($maxNumber, intval($matches[1]));
            }
        }
        
        return sprintf('%s-%03d', $prefix, $maxNumber + 1);
    }
}