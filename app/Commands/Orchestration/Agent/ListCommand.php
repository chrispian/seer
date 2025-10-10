<?php

namespace App\Commands\Orchestration\Agent;

use App\Commands\BaseCommand;

class ListCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get agents from the orchestration system
        $agents = $this->getAgents();

        return [
            'type' => 'agent-profile',
            'component' => 'AgentProfileListModal',
            'data' => $agents,
        ];
    }

    private function getAgents(): array
    {
        // Use AgentProfile model (same as AgentsListTool)
        if (class_exists(\App\Models\AgentProfile::class)) {
            $agents = \App\Models\AgentProfile::query()
                ->orderBy('name')
                ->limit(50)
                ->get();

            return $agents->map(function ($agent) {
                // TODO: Add real assignment counts when assignment system is available
                $activeAssignments = 0;
                $totalAssignments = 0;

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'slug' => $agent->slug,
                    'status' => $agent->status,
                    'type' => $agent->type,
                    'mode' => $agent->mode,
                    'description' => $agent->description ?? null,
                    'capabilities' => $agent->capabilities ?? [],
                    'constraints' => $agent->constraints ?? [],
                    'tools' => $agent->tools ?? [],
                    'metadata' => $agent->metadata ?? [],
                    'active_assignments' => $activeAssignments,
                    'total_assignments' => $totalAssignments,
                    'created_at' => $agent->created_at?->toISOString(),
                    'updated_at' => $agent->updated_at?->toISOString(),
                ];
            })->all();
        }

        // Fallback: Return empty list if model doesn't exist
        return [];
    }

    public static function getName(): string
    {
        return 'Agent List';
    }

    public static function getDescription(): string
    {
        return 'List all agents with their status and capabilities';
    }

    public static function getUsage(): string
    {
        return '/agents';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
