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
            'avatar' => 'nullable|image|max:2048',
        ]);

        $designation = strtoupper(substr(md5($validated['name'].time()), 0, 5));

        $profileId = $validated['agent_profile_id'] ?? \App\Models\AgentProfile::first()?->id;

        if (!$profileId) {
            return response()->json([
                'success' => false,
                'message' => 'No agent profile available. Please create an agent profile first.',
            ], 422);
        }

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $agent = Agent::create([
            'name' => $validated['name'],
            'designation' => $designation,
            'agent_profile_id' => $profileId,
            'persona' => $validated['persona'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'avatar_path' => $avatarPath,
            'tool_config' => [],
            'metadata' => [],
        ]);

        $agent->append('avatar_url');

        return response()->json([
            'success' => true,
            'message' => "Agent '{$agent->name}' created successfully",
            'data' => $agent,
        ], 201);
    }

    public function show(string $id)
    {
        $agent = Agent::with('agentProfile')->findOrFail($id);
        $agent->append('avatar_url');

        return response()->json([
            'success' => true,
            'data' => $agent,
        ]);
    }
}
