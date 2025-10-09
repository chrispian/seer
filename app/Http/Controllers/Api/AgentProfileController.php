<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Orchestration\ModelResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $agents = $service->list([
            'status' => $request->input('status'),
            'type' => $request->input('type'),
            'mode' => $request->input('mode'),
            'search' => $request->input('search'),
            'limit' => $request->input('limit', 100),
            'include' => ['capabilities', 'constraints', 'tools'],
        ]);

        return response()->json($agents['data'] ?? []);
    }

    public function show(string $id): JsonResponse
    {
        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $model = ModelResolver::resolve('agent_model', 'App\\Models\\AgentProfile');
        $agent = $model::findOrFail($id);

        $detail = $service->detail($agent, [
            'assignments_limit' => 10,
        ]);

        return response()->json($detail);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'slug' => ['nullable', 'string'],
            'type' => ['required', 'string'],
            'mode' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string'],
            'constraints' => ['nullable', 'array'],
            'constraints.*' => ['string'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $agent = $service->save($validated, true);

        $detail = $service->detail($agent, [
            'assignments_limit' => 10,
        ]);

        return response()->json($detail, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string'],
            'slug' => ['sometimes', 'string'],
            'type' => ['sometimes', 'string'],
            'mode' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string'],
            'constraints' => ['nullable', 'array'],
            'constraints.*' => ['string'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $validated['id'] = $id;

        $service = ModelResolver::resolveService('agent_service', 'App\\Services\\AgentOrchestrationService');

        $agent = $service->save($validated, true);

        $detail = $service->detail($agent, [
            'assignments_limit' => 10,
        ]);

        return response()->json($detail);
    }

    public function destroy(string $id): JsonResponse
    {
        $model = ModelResolver::resolve('agent_model', 'App\\Models\\AgentProfile');
        $agent = $model::findOrFail($id);

        $agent->delete();

        return response()->json(['message' => 'Agent profile deleted successfully']);
    }
}
