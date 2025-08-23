<?php

namespace App\Livewire;

use App\Models\Fragment;
use Livewire\Component;

class TodoItem extends Component
{
    public Fragment $fragment;

    public function toggle()
    {
        $state = $this->fragment->state ?? [];
        $newStatus = ($state['status'] ?? 'open') === 'complete' ? 'open' : 'complete';
        
        $state['status'] = $newStatus;
        
        // Add completion timestamp when marking as complete
        if ($newStatus === 'complete') {
            $state['completed_at'] = now()->toISOString();
        } else {
            // Remove completion timestamp when reopening
            unset($state['completed_at']);
        }

        $this->fragment->state = $state;
        $this->fragment->save();
    }

    public function render()
    {
        return view('livewire.todo-item');
    }
}
