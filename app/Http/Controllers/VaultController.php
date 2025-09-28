<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VaultController extends Controller
{
    public function index(Request $request)
    {
        $vaults = Vault::ordered()->get()->map(function ($vault) {
            return [
                'id' => $vault->id,
                'name' => $vault->name,
                'description' => $vault->description,
                'is_default' => $vault->is_default,
                'sort_order' => $vault->sort_order,
                'created_at' => $vault->created_at,
                'updated_at' => $vault->updated_at,
                'projects_count' => $vault->projects()->count(),
                'chat_sessions_count' => $vault->chatSessions()->count(),
                'fragments_count' => $vault->fragments()->count(),
            ];
        });

        return response()->json([
            'vaults' => $vaults,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:vaults,name',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request) {
            // If this is being set as default, unset existing default
            if ($request->input('is_default', false)) {
                Vault::where('is_default', true)->update(['is_default' => false]);
            }

            // Get next sort order
            $maxSortOrder = Vault::max('sort_order') ?? 0;

            $vault = Vault::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_default' => $request->input('is_default', false),
                'sort_order' => $maxSortOrder + 1,
                'metadata' => [],
            ]);

            // Create a default project for the new vault
            $project = Project::create([
                'vault_id' => $vault->id,
                'name' => 'Default',
                'description' => 'Default project for '.$vault->name,
                'is_default' => true,
                'sort_order' => 1,
                'metadata' => [],
            ]);

            return response()->json([
                'vault' => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                    'description' => $vault->description,
                    'is_default' => $vault->is_default,
                    'sort_order' => $vault->sort_order,
                    'created_at' => $vault->created_at,
                    'updated_at' => $vault->updated_at,
                    'projects_count' => 1,
                    'chat_sessions_count' => 0,
                    'fragments_count' => 0,
                ],
                'default_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'vault_id' => $project->vault_id,
                    'is_default' => $project->is_default,
                    'sort_order' => $project->sort_order,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ],
            ], 201);
        });
    }

    public function show(Vault $vault)
    {
        return response()->json([
            'vault' => [
                'id' => $vault->id,
                'name' => $vault->name,
                'description' => $vault->description,
                'is_default' => $vault->is_default,
                'sort_order' => $vault->sort_order,
                'created_at' => $vault->created_at,
                'updated_at' => $vault->updated_at,
                'projects_count' => $vault->projects()->count(),
                'chat_sessions_count' => $vault->chatSessions()->count(),
                'fragments_count' => $vault->fragments()->count(),
            ],
        ]);
    }

    public function update(Request $request, Vault $vault)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:vaults,name,'.$vault->id,
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request, $vault) {
            // If this is being set as default, unset existing default
            if ($request->input('is_default', false) && ! $vault->is_default) {
                Vault::where('is_default', true)->update(['is_default' => false]);
            }

            $vault->update($request->only(['name', 'description', 'is_default']));

            return response()->json([
                'vault' => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                    'description' => $vault->description,
                    'is_default' => $vault->is_default,
                    'sort_order' => $vault->sort_order,
                    'created_at' => $vault->created_at,
                    'updated_at' => $vault->updated_at,
                    'projects_count' => $vault->projects()->count(),
                    'chat_sessions_count' => $vault->chatSessions()->count(),
                    'fragments_count' => $vault->fragments()->count(),
                ],
            ]);
        });
    }

    public function destroy(Vault $vault)
    {
        // Prevent deleting the last vault
        if (Vault::count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last vault',
            ], 422);
        }

        // If this is the default vault, set another vault as default
        if ($vault->is_default) {
            $newDefaultVault = Vault::where('id', '!=', $vault->id)->first();
            if ($newDefaultVault) {
                $newDefaultVault->update(['is_default' => true]);
            }
        }

        $vault->delete();

        return response()->json([
            'message' => 'Vault deleted successfully',
        ]);
    }

    public function setDefault(Vault $vault)
    {
        return DB::transaction(function () use ($vault) {
            // Unset current default
            Vault::where('is_default', true)->update(['is_default' => false]);

            // Set this vault as default
            $vault->update(['is_default' => true]);

            // Get the default project for this vault
            $defaultProject = Project::where('vault_id', $vault->id)
                ->where('is_default', true)
                ->first();

            // If no default project exists, set the first project as default
            if (! $defaultProject) {
                $defaultProject = Project::where('vault_id', $vault->id)
                    ->ordered()
                    ->first();
                if ($defaultProject) {
                    $defaultProject->update(['is_default' => true]);
                }
            }

            return response()->json([
                'vault' => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                    'description' => $vault->description,
                    'is_default' => $vault->is_default,
                    'sort_order' => $vault->sort_order,
                    'created_at' => $vault->created_at,
                    'updated_at' => $vault->updated_at,
                    'projects_count' => $vault->projects()->count(),
                    'chat_sessions_count' => $vault->chatSessions()->count(),
                    'fragments_count' => $vault->fragments()->count(),
                ],
                'default_project_id' => $defaultProject?->id,
                'context_updated' => true,
            ]);
        });
    }
}
