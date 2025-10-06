<?php

namespace App\Commands;

class TodoCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get todo items from fragments or dedicated todo system
        $fragments = $this->getTodos();
        
        return [
            'type' => 'todo',
            'component' => 'TodoManagementModal',
            'data' => $fragments,
            'panelData' => [
                'fragments' => $fragments  // useTodoData expects this format
            ]
        ];
    }
    
    private function getTodos(): array
    {
        if (class_exists(\App\Models\Fragment::class)) {
            // Return fragments in their original format so useTodoData can transform them
            $fragments = \App\Models\Fragment::query()
                ->where('type', 'todo')
                ->latest()
                ->limit(50)
                ->get()
                ->map(function ($fragment) {
                    // Return fragment data in the format useTodoData expects
                    return [
                        'id' => $fragment->id,
                        'title' => $fragment->title,
                        'message' => $fragment->message,
                        'type' => $fragment->type,
                        'tags' => $fragment->tags,
                        'state' => $fragment->state,
                        'metadata' => $fragment->metadata,
                        'created_at' => $fragment->created_at?->toISOString(),
                        'updated_at' => $fragment->updated_at?->toISOString(),
                        'pinned' => $fragment->pinned ?? false,
                    ];
                })
                ->all();
                
            return $fragments;
        }
        
        return [];
    }
    
    public static function getName(): string
    {
        return 'Todo Manager';
    }
    
    public static function getDescription(): string
    {
        return 'Manage todo items and task lists';
    }
    
    public static function getUsage(): string
    {
        return '/todo';
    }
    
    public static function getCategory(): string
    {
        return 'Productivity';
    }
}