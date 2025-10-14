<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Project;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatSessionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'vault_id' => 'nullable|integer|exists:vaults,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $vaultId = $request->input('vault_id');
        $projectId = $request->input('project_id');
        $limit = $request->input('limit', 20);

        // Get default vault if none specified
        if (! $vaultId) {
            $defaultVault = Vault::where('is_default', true)->first();
            $vaultId = $defaultVault?->id;
        }

        $query = ChatSession::query()
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit);

        if ($vaultId) {
            $query->where('vault_id', $vaultId);
            if ($projectId) {
                $query->where('project_id', $projectId);
            }
        }

        $sessions = $query->get()->map(function ($session) {
            return [
                'id' => $session->id,
                'title' => $session->title,
                'channel_display' => $session->channel_display,
                'message_count' => $session->message_count,
                'last_activity_at' => $session->last_activity_at?->diffForHumans(),
                'is_pinned' => $session->is_pinned,
                'sort_order' => $session->sort_order,
                'vault_id' => $session->vault_id,
                'project_id' => $session->project_id,
            ];
        });

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    public function pinned(Request $request)
    {
        $request->validate([
            'vault_id' => 'nullable|integer|exists:vaults,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $vaultId = $request->input('vault_id');
        $projectId = $request->input('project_id');

        // Get default vault if none specified
        if (! $vaultId) {
            $defaultVault = Vault::where('is_default', true)->first();
            $vaultId = $defaultVault?->id;
        }

        $query = ChatSession::pinned();

        if ($vaultId) {
            $query->where('vault_id', $vaultId);
            if ($projectId) {
                $query->where('project_id', $projectId);
            }
        }

        $sessions = $query->get()->map(function ($session) {
            return [
                'id' => $session->id,
                'title' => $session->title,
                'channel_display' => $session->channel_display,
                'message_count' => $session->message_count,
                'last_activity_at' => $session->last_activity_at?->diffForHumans(),
                'is_pinned' => $session->is_pinned,
                'sort_order' => $session->sort_order,
                'vault_id' => $session->vault_id,
                'project_id' => $session->project_id,
            ];
        });

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vault_id' => 'nullable|integer|exists:vaults,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'title' => 'nullable|string|max:255',
        ]);

        $vaultId = $request->input('vault_id');
        $projectId = $request->input('project_id');

        // Get default vault if none specified
        if (! $vaultId) {
            $defaultVault = Vault::where('is_default', true)->first();
            $vaultId = $defaultVault?->id;
        }

        $session = new ChatSession;
        $session->vault_id = $vaultId;
        $session->project_id = $projectId;
        $session->title = $request->input('title', 'New Chat');
        $session->messages = [];
        $session->metadata = [];
        $session->message_count = 0;
        $session->last_activity_at = now();
        $session->is_active = true;
        $session->save();

        return response()->json([
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'channel_display' => $session->channel_display,
                'message_count' => $session->message_count,
                'last_activity_at' => $session->last_activity_at?->diffForHumans(),
                'is_pinned' => $session->is_pinned,
                'sort_order' => $session->sort_order,
                'vault_id' => $session->vault_id,
                'project_id' => $session->project_id,
                'messages' => $session->messages,
                'metadata' => $session->metadata,
            ],
        ], 201);
    }

    public function show(ChatSession $chatSession)
    {
        // Load relationships
        $chatSession->load(['vault', 'project']);

        // Determine if session is active (has activity in last hour)
        $isActive = $chatSession->last_activity_at &&
                   $chatSession->last_activity_at->diffInMinutes(now()) < 60;

        return response()->json([
            'session' => [
                'id' => $chatSession->id,
                'title' => $chatSession->title,
                'channel_display' => $chatSession->channel_display,
                'message_count' => $chatSession->message_count,
                'last_activity_at' => $chatSession->last_activity_at?->toISOString(),
                'is_pinned' => $chatSession->is_pinned,
                'is_active' => $isActive,
                'sort_order' => $chatSession->sort_order,
                'vault_id' => $chatSession->vault_id,
                'project_id' => $chatSession->project_id,
                'messages' => $chatSession->messages,
                'metadata' => $chatSession->metadata,
                'model_provider' => $chatSession->model_provider,
                'model_name' => $chatSession->model_name,
                'vault' => $chatSession->vault ? [
                    'id' => $chatSession->vault->id,
                    'name' => $chatSession->vault->name,
                ] : null,
                'project' => $chatSession->project ? [
                    'id' => $chatSession->project->id,
                    'name' => $chatSession->project->name,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, ChatSession $chatSession)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'messages' => 'nullable|array',
            'metadata' => 'nullable|array',
            'model_provider' => 'nullable|string|max:255',
            'model_name' => 'nullable|string|max:255',
        ]);

        if ($request->has('title')) {
            $chatSession->title = $request->input('title');
        }

        if ($request->has('messages')) {
            $chatSession->messages = $request->input('messages');
            $chatSession->message_count = count($request->input('messages'));
        }

        if ($request->has('metadata')) {
            $chatSession->metadata = $request->input('metadata');
        }

        if ($request->has('model_provider')) {
            $chatSession->model_provider = $request->input('model_provider');
        }

        if ($request->has('model_name')) {
            $chatSession->model_name = $request->input('model_name');
        }

        $chatSession->last_activity_at = now();
        $chatSession->save();

        // Update title from messages if not explicitly set
        if (! $request->has('title')) {
            $chatSession->updateTitleFromMessages();
        }

        return response()->json([
            'session' => [
                'id' => $chatSession->id,
                'title' => $chatSession->title,
                'channel_display' => $chatSession->channel_display,
                'message_count' => $chatSession->message_count,
                'last_activity_at' => $chatSession->last_activity_at?->diffForHumans(),
                'is_pinned' => $chatSession->is_pinned,
                'sort_order' => $chatSession->sort_order,
                'vault_id' => $chatSession->vault_id,
                'project_id' => $chatSession->project_id,
                'messages' => $chatSession->messages,
                'metadata' => $chatSession->metadata,
            ],
        ]);
    }

    public function destroy(ChatSession $chatSession)
    {
        $chatSession->update(['is_active' => false]);
        $chatSession->delete();

        return response()->json(['message' => 'Chat session deleted successfully']);
    }

    public function togglePin(Request $request, ChatSession $chatSession)
    {
        $chatSession->togglePin();

        return response()->json([
            'session' => [
                'id' => $chatSession->id,
                'title' => $chatSession->title,
                'channel_display' => $chatSession->channel_display,
                'message_count' => $chatSession->message_count,
                'last_activity_at' => $chatSession->last_activity_at?->diffForHumans(),
                'is_pinned' => $chatSession->is_pinned,
                'sort_order' => $chatSession->sort_order,
                'vault_id' => $chatSession->vault_id,
                'project_id' => $chatSession->project_id,
            ],
        ]);
    }

    public function updatePinOrder(Request $request)
    {
        $request->validate([
            'sessions' => 'required|array',
            'sessions.*.id' => 'required|integer|exists:chat_sessions,id',
            'sessions.*.sort_order' => 'required|integer',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('sessions') as $sessionData) {
                ChatSession::where('id', $sessionData['id'])
                    ->update(['sort_order' => $sessionData['sort_order']]);
            }
        });

        return response()->json(['message' => 'Pin order updated successfully']);
    }

    public function getContext()
    {
        $vaults = Vault::ordered()->get()->map(fn ($vault) => [
            'id' => $vault->id,
            'name' => $vault->name,
            'description' => $vault->description,
            'is_default' => $vault->is_default,
        ]);

        $defaultVault = $vaults->firstWhere('is_default', true);
        $projects = collect();
        $defaultProject = null;

        if ($defaultVault) {
            $projects = Project::where('vault_id', $defaultVault['id'])
                ->ordered()
                ->get()
                ->map(fn ($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'vault_id' => $project->vault_id,
                    'is_default' => $project->is_default,
                ]);

            // Get the default project for this vault
            $defaultProject = $projects->firstWhere('is_default', true);
        }

        return response()->json([
            'vaults' => $vaults,
            'projects' => $projects,
            'current_vault_id' => $defaultVault['id'] ?? null,
            'current_project_id' => $defaultProject['id'] ?? optional($projects->first())->id ?? null,
            'context_timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Update the model for a chat session
     */
    public function updateModel(Request $request, ChatSession $chatSession)
    {
        $request->validate([
            'ai_model_id' => 'required|integer|exists:models,id',
        ]);

        $aiModelId = $request->input('ai_model_id');
        $aiModel = \App\Models\AIModel::with('provider')->findOrFail($aiModelId);

        // Update with FK and maintain legacy columns for backward compatibility
        $chatSession->update([
            'ai_model_id' => $aiModel->id,
            'model_provider' => $aiModel->provider->provider,  // Keep for backward compatibility
            'model_name' => $aiModel->model_id,  // Keep for backward compatibility
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chatSession->id,
                'ai_model_id' => $chatSession->ai_model_id,
                'model_provider' => $chatSession->model_provider,
                'model_name' => $chatSession->model_name,
            ],
        ]);
    }

    public function getProjects()
    {
        $defaultVault = Vault::where('is_default', true)->first();

        if (! $defaultVault) {
            return response()->json([
                'success' => false,
                'error' => 'No default vault found',
            ], 404);
        }

        $projects = Project::where('vault_id', $defaultVault->id)
            ->ordered()
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'path' => $project->path,
                'is_default' => $project->is_default,
            ]);

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    public function updateProject(Request $request, ChatSession $chatSession)
    {
        $request->validate([
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $chatSession->update([
            'project_id' => $request->input('project_id'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chatSession->id,
                'project_id' => $chatSession->project_id,
            ],
        ]);
    }

    public function updatePaths(Request $request, ChatSession $chatSession)
    {
        $request->validate([
            'additional_paths' => 'nullable|array',
            'additional_paths.*' => 'string|max:500',
        ]);

        $paths = $request->input('additional_paths', []);

        foreach ($paths as $path) {
            if (strpos($path, '..') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Path traversal attempts are not allowed',
                ], 400);
            }
        }

        $chatSession->update([
            'additional_paths' => $paths,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chatSession->id,
                'additional_paths' => $chatSession->additional_paths,
            ],
        ]);
    }
}
