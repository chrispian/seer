<?php

namespace App\Commands;

class SearchCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get search results from fragments
        $results = $this->getSearchResults();
        
        return [
            'type' => 'fragment',
            'component' => 'FragmentListModal',
            'data' => $results
        ];
    }
    
    private function getSearchResults(): array
    {
        // Get search query from command context if available
        $query = $this->getSearchQuery();
        
        if (class_exists(\App\Models\Fragment::class)) {
            $fragmentQuery = \App\Models\Fragment::query()->with('category');
            
            if (!empty($query)) {
                // Simple text search for now
                $fragmentQuery->where(function ($q) use ($query) {
                    $q->where('message', 'like', '%' . $query . '%')
                      ->orWhere('title', 'like', '%' . $query . '%');
                });
            }
            
            $fragments = $fragmentQuery
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
    
    private function getSearchQuery(): ?string
    {
        // TODO: Extract from command context/arguments
        // For now, return null to show all fragments
        return null;
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