<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'vault_id' => 'nullable|integer|exists:vaults,id',
        ]);

        $vaultId = $request->input('vault_id');

        $query = Project::with('vault')->ordered();

        if ($vaultId) {
            $query->where('vault_id', $vaultId);
        }

        $projects = $query->get()->map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'vault_id' => $project->vault_id,
                'vault_name' => $project->vault->name,
                'is_default' => $project->is_default,
                'sort_order' => $project->sort_order,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'chat_sessions_count' => $project->chatSessions()->count(),
                'fragments_count' => $project->fragments()->count(),
            ];
        });

        return response()->json([
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vault_id' => 'required|integer|exists:vaults,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request) {
            $vaultId = $request->input('vault_id');

            // If this is being set as default for the vault, unset existing default
            if ($request->input('is_default', false)) {
                Project::where('vault_id', $vaultId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Get next sort order for this vault
            $maxSortOrder = Project::where('vault_id', $vaultId)->max('sort_order') ?? 0;

            $project = Project::create([
                'vault_id' => $vaultId,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_default' => $request->input('is_default', false),
                'sort_order' => $maxSortOrder + 1,
                'metadata' => [],
            ]);

            $project->load('vault');

            return response()->json([
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'vault_id' => $project->vault_id,
                    'vault_name' => $project->vault->name,
                    'is_default' => $project->is_default,
                    'sort_order' => $project->sort_order,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                    'chat_sessions_count' => 0,
                    'fragments_count' => 0,
                ],
            ], 201);
        });
    }

    public function show(Project $project)
    {
        $project->load('vault');

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'vault_id' => $project->vault_id,
                'vault_name' => $project->vault->name,
                'is_default' => $project->is_default,
                'sort_order' => $project->sort_order,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'chat_sessions_count' => $project->chatSessions()->count(),
                'fragments_count' => $project->fragments()->count(),
            ],
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request, $project) {
            // If this is being set as default for the vault, unset existing default
            if ($request->input('is_default', false) && !$project->is_default) {
                Project::where('vault_id', $project->vault_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $project->update($request->only(['name', 'description', 'is_default']));
            $project->load('vault');

            return response()->json([
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'vault_id' => $project->vault_id,
                    'vault_name' => $project->vault->name,
                    'is_default' => $project->is_default,
                    'sort_order' => $project->sort_order,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                    'chat_sessions_count' => $project->chatSessions()->count(),
                    'fragments_count' => $project->fragments()->count(),
                ],
            ]);
        });
    }

    public function destroy(Project $project)
    {
        $vaultId = $project->vault_id;

        // Prevent deleting the last project in a vault
        if (Project::where('vault_id', $vaultId)->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last project in a vault',
            ], 422);
        }

        // If this is the default project, set another project as default
        if ($project->is_default) {
            $newDefaultProject = Project::where('vault_id', $vaultId)
                ->where('id', '!=', $project->id)
                ->first();
            if ($newDefaultProject) {
                $newDefaultProject->update(['is_default' => true]);
            }
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    public function getForVault(Vault $vault)
    {
        $projects = $vault->projects()->ordered()->get()->map(function ($project) use ($vault) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'vault_id' => $project->vault_id,
                'vault_name' => $vault->name,
                'is_default' => $project->is_default,
                'sort_order' => $project->sort_order,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'chat_sessions_count' => $project->chatSessions()->count(),
                'fragments_count' => $project->fragments()->count(),
            ];
        });

        return response()->json([
            'projects' => $projects,
        ]);
    }

    public function setDefault(Project $project)
    {
        return DB::transaction(function () use ($project) {
            // Unset current default within the same vault
            Project::where('vault_id', $project->vault_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            
            // Set this project as default
            $project->update(['is_default' => true]);
            
            $project->load('vault');

            return response()->json([
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'vault_id' => $project->vault_id,
                    'vault_name' => $project->vault->name,
                    'is_default' => $project->is_default,
                    'sort_order' => $project->sort_order,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                    'chat_sessions_count' => $project->chatSessions()->count(),
                    'fragments_count' => $project->fragments()->count(),
                ],
                'context_updated' => true,
            ]);
        });
    }
}