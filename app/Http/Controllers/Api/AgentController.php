<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\AgentDesignationGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentDesignationGenerator $designationGenerator
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Agent::with('agentProfile')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->whereIn('status', (array) $request->input('status'));
        }

        if ($request->has('agent_profile_id')) {
            $query->where('agent_profile_id', $request->input('agent_profile_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $limit = $request->input('limit', 50);
        $agents = $query->limit($limit)->get();

        return response()->json($agents);
    }

    public function show(string $id): JsonResponse
    {
        $agent = Agent::with('agentProfile')->findOrFail($id);

        return response()->json($agent);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'agent_profile_id' => ['required', 'exists:agent_profiles,id'],
            'persona' => ['nullable', 'string'],
            'tool_config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive,archived'],
        ]);

        $validated['designation'] = $this->designationGenerator->generate();

        $agent = Agent::create($validated);
        $agent->load('agentProfile');

        return response()->json($agent, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'agent_profile_id' => ['sometimes', 'exists:agent_profiles,id'],
            'persona' => ['nullable', 'string'],
            'tool_config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
        ]);

        $agent->update($validated);

        if (array_key_exists('name', $validated) || array_key_exists('persona', $validated) || array_key_exists('tool_config', $validated)) {
            $agent->incrementVersion();
        }

        $agent->load('agentProfile');

        return response()->json($agent);
    }

    public function destroy(string $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);
        $agent->delete();

        return response()->json(['message' => 'Agent deleted successfully']);
    }

    public function generateDesignation(): JsonResponse
    {
        return response()->json([
            'designation' => $this->designationGenerator->generate(),
        ]);
    }

    public function uploadAvatar(Request $request, string $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:5120'],
        ]);

        if ($agent->avatar_path && Storage::disk('public')->exists($agent->avatar_path)) {
            Storage::disk('public')->delete($agent->avatar_path);
        }

        $file = $request->file('avatar');
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'avatars/agents',
            $agent->id.'.'.$extension,
            'public'
        );

        $agent->update(['avatar_path' => $path]);
        $agent->load('agentProfile');

        return response()->json($agent);
    }
}
