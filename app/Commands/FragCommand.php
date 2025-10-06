<?php

namespace App\Commands;

class FragCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get recent fragments (similar to recall but with different focus)
        $fragments = $this->getFragments();
        
        return [
            'type' => 'fragment',
            'component' => 'FragmentListModal',
            'data' => $fragments
        ];
    }
    
    private function getFragments(): array
    {
        if (class_exists(\App\Models\Fragment::class)) {
            $fragments = \App\Models\Fragment::query()
                ->with('category')
                ->latest()
                ->limit(30)
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
                        'preview' => \Illuminate\Support\Str::limit($fragment->message, 150),
                    ];
                })
                ->all();
                
            return $fragments;
        }
        
        return [];
    }
    
    public static function getName(): string
    {
        return 'Fragment Manager';
    }
    
    public static function getDescription(): string
    {
        return 'Manage and browse fragments in the system';
    }
    
    public static function getUsage(): string
    {
        return '/frag';
    }
    
    public static function getCategory(): string
    {
        return 'Content';
    }
}