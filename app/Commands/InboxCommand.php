<?php

namespace App\Commands;

use App\Models\Fragment;
use Illuminate\Support\Str;

class InboxCommand extends BaseCommand
{
    public function handle(): array
    {
        $results = $this->getInboxFragments();

        return [
            'type' => 'fragment',
            'component' => 'FragmentListModal',
            'data' => $results,
        ];
    }

    private function getInboxFragments(): array
    {
        $fragments = Fragment::query()
            ->with('category')
            ->where(function ($query) {
                $query->whereNotNull('inbox_status')
                    ->orWhereJsonContains('metadata->needs_review', true)
                    ->orWhere('type', 'inbox');
            })
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
                    'preview' => Str::limit($fragment->message, 200),
                ];
            })
            ->all();

        return $fragments;
    }

    public static function getName(): string
    {
        return 'Inbox';
    }

    public static function getDescription(): string
    {
        return 'View fragments marked for inbox review';
    }

    public static function getUsage(): string
    {
        return '/inbox';
    }

    public static function getCategory(): string
    {
        return 'Communication';
    }
}
