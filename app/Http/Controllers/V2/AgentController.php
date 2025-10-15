<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'agent_profile_id' => 'nullable|uuid|exists:agent_profiles,id',
            'persona' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $designation = strtoupper(substr(md5($validated['name'].time()), 0, 5));

        $agent = Agent::create([
            'name' => $validated['name'],
            'designation' => $designation,
            'agent_profile_id' => $validated['agent_profile_id'] ?? null,
            'persona' => $validated['persona'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'tool_config' => [],
            'metadata' => [],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Agent '{$agent->name}' created successfully",
            'data' => $agent,
        ], 201);
    }

    public function show(string $id)
    {
        $agent = Agent::with('agentProfile')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $agent,
        ]);
    }
}
