<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orchestration\OrchestrationPMToolsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrchestrationPMToolsController extends Controller
{
    public function __construct(
        private readonly OrchestrationPMToolsService $pmToolsService
    ) {}

    public function generateADR(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'deciders' => 'nullable|string|max:255',
            'context' => 'nullable|string',
            'decision' => 'nullable|string',
        ]);

        $result = $this->pmToolsService->generateADR(
            $validated['title'],
            [
                'deciders' => $validated['deciders'] ?? null,
                'context' => $validated['context'] ?? null,
                'decision' => $validated['decision'] ?? null,
            ]
        );

        return response()->json($result);
    }

    public function generateBugReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => ['required', Rule::in(['P0', 'P1', 'P2', 'P3'])],
            'category' => 'nullable|string|max:100',
            'component' => 'nullable|string|max:100',
            'effort' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'reproduction_steps' => 'nullable|string',
            'expected_behavior' => 'nullable|string',
            'actual_behavior' => 'nullable|string',
        ]);

        $result = $this->pmToolsService->generateBugReport(
            $validated['title'],
            $validated['priority'],
            [
                'category' => $validated['category'] ?? null,
                'component' => $validated['component'] ?? null,
                'effort' => $validated['effort'] ?? null,
                'description' => $validated['description'] ?? null,
                'reproduction_steps' => $validated['reproduction_steps'] ?? null,
                'expected_behavior' => $validated['expected_behavior'] ?? null,
                'actual_behavior' => $validated['actual_behavior'] ?? null,
            ]
        );

        return response()->json($result);
    }

    public function updateTaskStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_code' => 'required|string|max:100',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'notes' => 'nullable|string',
            'agent_id' => 'nullable|integer|exists:agent_profiles,id',
            'session_key' => 'nullable|string|max:100',
            'emit_event' => 'nullable|boolean',
            'sync_to_file' => 'nullable|boolean',
        ]);

        $result = $this->pmToolsService->updateTaskStatus(
            $validated['task_code'],
            $validated['status'],
            [
                'notes' => $validated['notes'] ?? null,
                'agent_id' => $validated['agent_id'] ?? null,
                'session_key' => $validated['session_key'] ?? null,
                'emit_event' => $validated['emit_event'] ?? true,
                'sync_to_file' => $validated['sync_to_file'] ?? true,
            ]
        );

        return response()->json($result);
    }

    public function generateStatusReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sprint_code' => 'required|string|max:100',
        ]);

        $report = $this->pmToolsService->generateStatusReport($validated['sprint_code']);

        return response()->json($report);
    }
}
