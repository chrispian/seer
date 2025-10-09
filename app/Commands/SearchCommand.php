<?php

namespace App\Commands;

class SearchCommand extends BaseCommand
{
    protected ?string $query = null;

    public function __construct(?string $argument = null)
    {
        $this->query = $argument;
    }

    public function handle(): array
    {
        $results = $this->getSearchResults();

        return [
            'type' => 'fragment',
            'component' => 'FragmentListModal',
            'data' => $results,
        ];
    }

    private function getSearchResults(): array
    {
        if (class_exists(\App\Models\Fragment::class)) {
            $fragmentQuery = \App\Models\Fragment::query()->with('category');

            if (! empty($this->query)) {
                $fragmentQuery->where(function ($q) {
                    $q->where('message', 'like', '%'.$this->query.'%')
                        ->orWhere('title', 'like', '%'.$this->query.'%');
                });
            }

            $fragments = $fragmentQuery
                ->latest()
                ->limit(200)
                ->get()
                ->map(function ($fragment) {
                    // Sanitize strings to ensure valid UTF-8
                    $message = mb_convert_encoding($fragment->message ?? '', 'UTF-8', 'UTF-8');
                    $title = mb_convert_encoding($fragment->title ?? '', 'UTF-8', 'UTF-8');

                    // Clean metadata - remove any non-UTF-8 safe values
                    $metadata = $fragment->metadata ?? [];
                    if (is_array($metadata)) {
                        array_walk_recursive($metadata, function (&$value) {
                            if (is_string($value)) {
                                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                            }
                        });
                    }

                    return [
                        'id' => $fragment->id,
                        'title' => $title,
                        'message' => $message,
                        'type' => $fragment->type,
                        'category' => $fragment->category?->name ?? null,
                        'metadata' => $metadata,
                        'created_at' => $fragment->created_at?->toISOString(),
                        'updated_at' => $fragment->updated_at?->toISOString(),
                        'created_human' => $fragment->created_at?->diffForHumans(),
                        'preview' => \Illuminate\Support\Str::limit($message, 200),
                    ];
                })
                ->all();

            return $fragments;
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Search';
    }

    public static function getDescription(): string
    {
        return 'Search through fragments and content';
    }

    public static function getUsage(): string
    {
        return '/search [query]';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
