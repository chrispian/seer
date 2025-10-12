<?php

namespace App\Commands;

class ProjectListCommand extends BaseCommand
{
    public function handle(): array
    {
        $projects = $this->getProjects();

        return $this->respond([
            'projects' => $projects,
        ]);
    }

    private function getProjects(): array
    {
        if (class_exists(\App\Models\Project::class)) {
            $projects = \App\Models\Project::query()
                ->with('vault')
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'vault_id' => $project->vault_id,
                        'vault_name' => $project->vault?->name,
                        'is_default' => $project->is_default,
                        'is_active' => $project->is_active ?? true,
                        'metadata' => $project->metadata ?? [],
                        'created_at' => $project->created_at?->toISOString(),
                        'updated_at' => $project->updated_at?->toISOString(),
                        'created_human' => $project->created_at?->diffForHumans(),
                    ];
                })
                ->all();

            return $projects;
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Project List';
    }

    public static function getDescription(): string
    {
        return 'List all projects in the system';
    }

    public static function getUsage(): string
    {
        return '/project';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
