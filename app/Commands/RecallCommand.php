<?php

namespace App\Commands;

class RecallCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get recent fragments for recall
        $fragments = $this->getRecentFragments();

        return [
            'type' => 'fragment',
            'component' => 'FragmentListModal',
            'data' => $fragments,
        ];
    }

    private function getRecentFragments(): array
    {
        if (class_exists(\App\Models\Fragment::class)) {
            $fragments = \App\Models\Fragment::query()
                ->with('category')
                ->latest()
                ->limit(50)
                ->get()
                ->map(function ($fragment) {
                    return [
                        'id' => $fragment->id,
                        'title' => $fragment->title,
                        'message' => $fragment->message,
                        'type' => $fragment->type,
                        'category' => $fragment->category?->name ?? null,
                        'metadata' => $fragment->metadata ?? [],
                        'created_at' => $fragment->created_at?->toISOString(),
                        'updated_at' => $fragment->updated_at?->toISOString(),
                        'created_human' => $fragment->created_at?->diffForHumans(),
                        'preview' => \Illuminate\Support\Str::limit($fragment->message, 200),
                    ];
                })
                ->all();

            return $fragments;
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Recall';
    }

    public static function getDescription(): string
    {
        return 'Recall and list recent fragments for review';
    }

    public static function getUsage(): string
    {
        return '/recall';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
