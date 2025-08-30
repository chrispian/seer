<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define initial types with their labels and colors
        $types = [
            // Essential system type
            ['value' => 'system', 'label' => 'System', 'color' => 'gray'],
            
            // Existing types from database query
            ['value' => 'idea', 'label' => 'Idea', 'color' => 'violet'],
            ['value' => 'log', 'label' => 'Log', 'color' => 'slate'],
            ['value' => 'note', 'label' => 'Note', 'color' => 'slate'],
            ['value' => 'reminder', 'label' => 'Reminder', 'color' => 'orange'],
            ['value' => 'research', 'label' => 'Research', 'color' => 'cyan'],
            ['value' => 'this', 'label' => 'This', 'color' => 'stone'],
            ['value' => 'todo', 'label' => 'Todo', 'color' => 'green'],
            
            // Common additional types
            ['value' => 'fragment', 'label' => 'Fragment', 'color' => 'gray'],
            ['value' => 'article', 'label' => 'Article', 'color' => 'blue'],
            ['value' => 'task', 'label' => 'Task', 'color' => 'green'],
            ['value' => 'meeting', 'label' => 'Meeting', 'color' => 'purple'],
            ['value' => 'contact', 'label' => 'Contact', 'color' => 'yellow'],
            ['value' => 'link', 'label' => 'Link', 'color' => 'indigo'],
            ['value' => 'insight', 'label' => 'Insight', 'color' => 'cyan'],
            ['value' => 'observation', 'label' => 'Observation', 'color' => 'sky'],
            ['value' => 'thought', 'label' => 'Thought', 'color' => 'blue'],
            ['value' => 'question', 'label' => 'Question', 'color' => 'indigo'],
            ['value' => 'bookmark', 'label' => 'Bookmark', 'color' => 'amber'],
        ];
        
        foreach ($types as $type) {
            Type::updateOrCreate(
                ['value' => $type['value']],
                [
                    'label' => $type['label'],
                    'color' => $type['color'],
                ]
            );
        }
    }
}
