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

        $state['status'] = ($state['status'] ?? 'open') === 'complete'
            ? 'open'
            : 'complete';

        $this->fragment->state = $state;
        $this->fragment->save();
    }

    public function render()
    {
        return view('livewire.todo-item');
    }
}
