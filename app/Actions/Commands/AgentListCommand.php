<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\AgentProfile;

class AgentListCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $agents = AgentProfile::query()
            ->orderBy('name')
            ->get();

        if ($agents->isEmpty()) {
            return new CommandResponse(
                message: '👤 No agents found. Import delegation data to populate agents.',
                type: 'agent',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => '👤 No agents found. Import delegation data to populate agents.',
                    'agents' => [],
                ],
            );
        }

        $agentData = $agents->map(function (AgentProfile $agent) {
            $activeAssignments = $agent->assignments()->active()->count();
            $totalAssignments = $agent->assignments()->count();

            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
                'type' => $agent->type?->value,
                'mode' => $agent->mode?->value,
                'status' => $agent->status?->value,
                'description' => $agent->description,
                'capabilities' => $agent->capabilities ?? [],
                'constraints' => $agent->constraints ?? [],
                'tools' => $agent->tools ?? [],
                'active_assignments' => $activeAssignments,
                'total_assignments' => $totalAssignments,
                'updated_at' => $agent->updated_at?->toIso8601String(),
            ];
        })->all();

        return new CommandResponse(
            message: '👤 Found **'.count($agentData).'** agent'.((count($agentData) !== 1) ? 's' : ''),
            type: 'agent',
            fragments: [],
            shouldResetChat: false,
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => '👤 Found **'.count($agentData).'** agent'.((count($agentData) !== 1) ? 's' : ''),
                'agents' => $agentData,
            ],
        );
    }
}
